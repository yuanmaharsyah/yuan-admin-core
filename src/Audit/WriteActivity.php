<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Audit;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Actions\LogActivityAction;

/**
 * The single write path of every audit row (model events and Audit::record()):
 * masking, request context, batch id and subject label.
 */
final class WriteActivity extends LogActivityAction
{
    public function __construct(private readonly Masker $masker, private readonly AuditContext $context) {}

    protected function transformChanges(Model $activity): void
    {
        /** @var ImmutableActivity $activity */
        $subject = $activity->subject;
        $properties = $activity->properties?->all() ?? [];
        $properties['meta'] ??= [];

        if ($subject !== null) {
            $properties['meta']['subject_label'] = method_exists($subject, 'auditLabel') ? $subject->auditLabel() : '#'.$subject->getKey();

            if ($activity->event === AuditEvent::Deleted->value && method_exists($subject, 'isForceDeleting') && $subject->isForceDeleting()) {
                $activity->event = AuditEvent::ForceDeleted->value;
                $activity->description = AuditEvent::ForceDeleted->value;
            }
        }

        $properties['context'] = $this->context->toArray();

        $activity->attribute_changes = collect($this->masker->mask($activity->attribute_changes?->all() ?? []));
        $activity->properties = collect($this->masker->mask($properties));
        $activity->batch_uuid = Audit::batchUuid();
    }
}
