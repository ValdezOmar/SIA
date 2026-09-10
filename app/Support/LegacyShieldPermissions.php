<?php

namespace App\Support;

use Filament\Resources\Resource;
use Filament\Widgets\Widget;
use Illuminate\Support\Str;

/** Preserve v3 permission keys; changing framework must not change access grants. */
final class LegacyShieldPermissions
{
    public static function key(string $entity, ?string $affix, string $subject): string
    {
        if ($entity === 'custom') {
            return $subject;
        }

        if (is_subclass_of($entity, Resource::class)) {
            $subject = (string) Str::of($entity)->afterLast('Resources\\')->before('Resource')
                ->replace('\\', '')->snake()->replace('_', '::');

            return Str::snake($affix).'_'.$subject;
        }

        return (is_subclass_of($entity, Widget::class) ? 'widget_' : 'page_').class_basename($entity);
    }
}
