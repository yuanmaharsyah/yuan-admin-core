<?php

declare(strict_types=1);

namespace Yuan\AdminCore\Audit;

/**
 * The audit event taxonomy (spec §7.1). Custom module actions are the only
 * other valid event names.
 */
enum AuditEvent: string
{
    case Created = 'created';
    case Updated = 'updated';
    case Deleted = 'deleted';
    case Restored = 'restored';
    case ForceDeleted = 'force_deleted';
    case Exported = 'exported';
    case AttachmentUploaded = 'attachment_uploaded';
    case AttachmentDownloaded = 'attachment_downloaded';
    case AttachmentDeleted = 'attachment_deleted';
    case Login = 'login';
    case Logout = 'logout';
    case LoginFailed = 'login_failed';
    case LoginLocked = 'login_locked';
    case PasswordChanged = 'password_changed';
    case PasswordReset = 'password_reset';
    case LevelChanged = 'level_changed';
    case UserActivated = 'user_activated';
    case UserDeactivated = 'user_deactivated';
    case PermissionsUpdated = 'permissions_updated';
    case SettingsUpdated = 'settings_updated';
    case AuditPruned = 'audit_pruned';

    public function label(): string
    {
        return match ($this) {
            self::Created => 'Dibuat',
            self::Updated => 'Diubah',
            self::Deleted => 'Dihapus',
            self::Restored => 'Dipulihkan',
            self::ForceDeleted => 'Dihapus permanen',
            self::Exported => 'Diunduh',
            self::AttachmentUploaded => 'Lampiran diunggah',
            self::AttachmentDownloaded => 'Lampiran diunduh',
            self::AttachmentDeleted => 'Lampiran dihapus',
            self::Login => 'Masuk',
            self::Logout => 'Keluar',
            self::LoginFailed => 'Login gagal',
            self::LoginLocked => 'Akun terkunci',
            self::PasswordChanged => 'Password diubah',
            self::PasswordReset => 'Password direset',
            self::LevelChanged => 'Level diubah',
            self::UserActivated => 'Diaktifkan',
            self::UserDeactivated => 'Dinonaktifkan',
            self::PermissionsUpdated => 'Hak akses diubah',
            self::SettingsUpdated => 'Pengaturan diubah',
            self::AuditPruned => 'Log dibersihkan',
        };
    }

    /**
     * @return 'success'|'info'|'destructive'|'warning'|'neutral'
     */
    public function badge(): string
    {
        return match ($this) {
            self::Created, self::UserActivated => 'success',
            self::Updated, self::Restored, self::LevelChanged, self::PermissionsUpdated, self::SettingsUpdated => 'info',
            self::Deleted, self::ForceDeleted => 'destructive',
            self::LoginFailed, self::LoginLocked, self::UserDeactivated => 'warning',
            default => 'neutral',
        };
    }

    /**
     * The fixed `log_name` of events that don't belong to a module.
     */
    public function logName(): ?string
    {
        return match ($this) {
            self::Login, self::Logout, self::LoginFailed, self::LoginLocked => 'auth',
            self::AuditPruned => 'system',
            default => null,
        };
    }
}
