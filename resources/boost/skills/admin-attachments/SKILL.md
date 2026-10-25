---
name: admin-attachments
description: "Use when a module record needs uploaded files (scans, documents, photos, attachments) stored privately with signed downloads."
---

# Add attachments to a model

The generator does all of this for a `name:file(single|multiple, required, pdf|jpg, 2048)` field. By hand:

1. Model: `use HasAttachments;` and declare the slots:
   ```php
   public function attachmentSlots(): array
   {
       return [
           'scan_ktp' => AttachmentSlot::single(__('Scan KTP'))->required()->mimes(['pdf', 'jpg', 'png'])->maxKb(2048),
           'lampiran' => AttachmentSlot::multiple(__('Lampiran')),
       ];
   }
   ```
2. Routes: `AttachmentController::routes({Name}::class);` (signed URL and download with `{slug}.view`, delete with `{slug}.edit`).
3. Input Data: `#[LiteralTypeScriptType('Record<string, File | File[] | null>')] public array $files = []`, rules `...AttachmentUploads::rules({Name}::class)` (on update pass the record), attributes `...AttachmentUploads::attributes({Name}::class)`.
4. Controller `store` / `update`: `AttachmentUploads::saveWith(fn () => ..., $data->files, $request->user())`, saving `$data->except('files')`. Create, Edit and Show send `'attachments' => AttachmentController::data($record, $request->user())`.
5. Pages: the form renders `<AttachmentFields slots={attachments.slots} value={data.files ?? {}} errors={errors} onChange={(files) => form.setData('files', files)} />`; the edit form posts with `update.form(id).action` (POST + `_method=PUT`, so files travel); Show has a Lampiran tab with `<AttachmentSlots slots={attachments.slots} />`.
6. Files land on the private `admin-core.attachments.disk` under random names; downloads are 5-minute signed URLs; upload, download and delete are audited. Never use the public disk.
7. `composer check`.
