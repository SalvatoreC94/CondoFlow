<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Audit trail for the `/platform` operator backoffice — kept entirely
 * separate from the tenant-facing `AuditLog` (different table, FK to
 * `platform_users` not `users`) since the two guards never share a user
 * model. Exists so any operator action on customer data (viewing/editing
 * an administrator, changing subscription status, editing shared
 * reference data) is traceable — see ARCHITECTURE.md.
 */
class PlatformAuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'platform_user_id',
        'action',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function platformUser(): BelongsTo
    {
        return $this->belongsTo(PlatformUser::class);
    }

    /**
     * @param  int|null  $actorId  Explicit acting platform user id — needed
     *                             for events (e.g. logout) fired after the guard has already cleared
     *                             its user, where `auth('platform')->id()` would no longer resolve.
     *                             Defaults to the currently authenticated platform user.
     */
    public static function record(
        string $action,
        ?Model $auditable = null,
        array $old = [],
        array $new = [],
        ?int $actorId = null,
    ): self {
        $request = request();

        return static::create([
            'platform_user_id' => $actorId ?? auth('platform')->id(),
            'action' => $action,
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
            'old_values' => $old ?: null,
            'new_values' => $new ?: null,
            'ip_address' => $request instanceof Request ? $request->ip() : null,
            'user_agent' => $request instanceof Request ? substr((string) $request->userAgent(), 0, 255) : null,
            'created_at' => now(),
        ]);
    }

    /**
     * Convenience wrappers used by the platform panel's resource pages/
     * table actions, so every Create/Edit/Delete/Restore action across
     * every resource logs the same way without repeating the boilerplate.
     */
    public static function logCreate(Model $record): self
    {
        return static::record(static::actionName($record, 'created'), $record, [], static::sanitize($record->getAttributes()));
    }

    /**
     * @param  array<string, mixed>  $original
     * @param  array<int, string>  $changedKeys
     */
    public static function logUpdate(Model $record, array $original, array $changedKeys): self
    {
        return static::record(
            static::actionName($record, 'updated'),
            $record,
            static::sanitize(Arr::only($original, $changedKeys)),
            static::sanitize($record->only($changedKeys)),
        );
    }

    public static function logDelete(Model $record): self
    {
        return static::record(static::actionName($record, 'deleted'), $record, static::sanitize($record->getAttributes()), []);
    }

    public static function logForceDelete(Model $record): self
    {
        return static::record(static::actionName($record, 'force_deleted'), $record, static::sanitize($record->getAttributes()), []);
    }

    public static function logRestore(Model $record): self
    {
        return static::record(static::actionName($record, 'restored'), $record, [], static::sanitize($record->getAttributes()));
    }

    protected static function actionName(Model $record, string $verb): string
    {
        return Str::snake(class_basename($record)).".{$verb}";
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected static function sanitize(array $attributes): array
    {
        return Arr::except($attributes, ['password', 'remember_token', 'invitation_token']);
    }
}
