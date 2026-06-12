<?php

namespace App\Services;

use App\Models\Attachment;
use App\Models\Media;
use Illuminate\Database\Eloquent\Model;

class AttachmentService
{
    public function attach(Media $media, Model $entity, ?string $caption = null): Attachment
    {
        $sequence = Attachment::where('attachable_type', get_class($entity))
            ->where('attachable_id', $entity->getKey())
            ->max('sequence') ?? 0;

        return Attachment::create([
            'media_id'        => $media->id,
            'attachable_type' => get_class($entity),
            'attachable_id'   => $entity->getKey(),
            'caption'         => $caption,
            'sequence'        => $sequence + 1,
        ]);
    }

    public function detach(Attachment $attachment): void
    {
        $attachment->delete();
    }

    public function reorder(array $orderedIds): void
    {
        foreach ($orderedIds as $seq => $id) {
            Attachment::where('id', $id)->update(['sequence' => $seq + 1]);
        }
    }
}
