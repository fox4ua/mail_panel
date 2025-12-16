# Code review notes

* **Zip handling hardening** — `ModulePackageService::deployFromZip()` fully trusts the uploaded ZIP size and extracts everything before doing any quotas or limits. Consider validating `UploadedFile::getSize()` against a configurable maximum and short-circuiting extraction to reduce DoS risk when large archives are uploaded. 【F:modules/System/ModuleCenter/Libraries/ModuleCenter/ModulePackageService.php†L15-L138】
* **Silent failures on uninstall** — `ModuleService::uninstall()` suppresses exceptions when deleting the DB row and module directory, which can leave partially removed modules without surfacing the error to operators. Logging the caught exceptions or propagating them would make cleanup issues visible. 【F:modules/System/ModuleCenter/Libraries/ModuleCenter/ModuleService.php†L282-L317】
* **Fail-open filter without observability** — `ModuleEnabledFilter` intentionally fails open on errors but also swallows the exception, giving no visibility into misconfiguration. Emitting a warning through the logger before returning would help detect modules that bypass checks due to unexpected errors. 【F:modules/System/Core/Filters/ModuleEnabledFilter.php†L22-L75】
* **Executing untrusted code during validation** — `ModulePackageService` requires the uploaded module's `Config/Info.php` while it still sits in a staging directory, meaning arbitrary PHP from the ZIP runs before any administrator can review it. To avoid remote code execution vectors, extract metadata without executing the file (e.g., parse it or enforce a signed manifest format) until after trust is established. 【F:modules/System/ModuleCenter/Libraries/ModuleCenter/ModulePackageService.php†L54-L124】
* **Minimal upload validation at the controller layer** — `ModuleInstallController::upload()` only checks `$zip->isValid()` before passing the file to the service. Add checks for MIME/extension consistency and a friendly size guard before dispatching, so the request can fail fast with a clear message instead of relying solely on deeper ZIP validation. 【F:modules/System/ModuleCenter/Controllers/ModuleInstallController.php†L18-L45】

## Example adjustments

Below is an illustrative (non-compiling) sketch of how the above recommendations could be applied:

```php
// modules/System/ModuleCenter/Controllers/ModuleInstallController.php
public function upload(): ResponseInterface
{
    $zip = $this->request->getFile('zip');

    if (! $zip?->isValid()) {
        return $this->failValidationErrors(lang('ModuleCenter.zipInvalid'));
    }

    $allowedMime = ['application/zip', 'application/x-zip-compressed'];
    if (! in_array($zip->getClientMimeType(), $allowedMime, true)) {
        return $this->failValidationErrors(lang('ModuleCenter.zipMimeNotAllowed'));
    }

    if ($zip->getSize() > config('ModuleCenter')->maxUploadBytes) {
        return $this->failValidationErrors(lang('ModuleCenter.zipTooLarge'));
    }

    // existing dispatch to ModulePackageService
}

// modules/System/ModuleCenter/Libraries/ModuleCenter/ModulePackageService.php
private function readInfoMetadata(string $tempDir): array
{
    $infoPath = $tempDir . '/Config/Info.php';
    if (! is_file($infoPath)) {
        throw new RuntimeException('Module info manifest missing');
    }

    // Example: read array return without executing arbitrary code
    $raw = file_get_contents($infoPath);
    $info = json_decode($raw, true, flags: JSON_THROW_ON_ERROR);

    return $info;
}
```
