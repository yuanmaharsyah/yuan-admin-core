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
    case AttachmentsSwept = 'attachments_swept';
    case TwoFactorEnabled = 'two_factor_enabled';
    case TwoFactorDisabled = 'two_factor_disabled';
    case TwoFactorFailed = 'two_factor_failed';
    case Registered = 'registered';

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
            self::AttachmentsSwept => 'Berkas tanpa data dibersihkan',
            self::TwoFactorEnabled => 'Verifikasi dua langkah aktif',
            self::TwoFactorDisabled => 'Verifikasi dua langkah mati',
            self::TwoFactorFailed => 'Kode verifikasi salah',
            self::Registered => 'Mendaftar',
        };
    }

    /**
     * @return 'success'|'info'|'destructive'|'warning'|'neutral'
     */
    public function badge(): string
    {
        return match ($this) {
            self::Created, self::UserActivated, self::TwoFactorEnabled, self::Registered => 'success',
            self::Updated, self::Restored, self::LevelChanged, self::PermissionsUpdated, self::SettingsUpdated => 'info',
            self::Deleted, self::ForceDeleted => 'destructive',
            self::LoginFailed, self::LoginLocked, self::UserDeactivated, self::TwoFactorDisabled, self::TwoFactorFailed => 'warning',
            default => 'neutral',
        };
    }

    /** The Log Aktivitas segment it counts under. */
    public function category(): AuditCategory
    {
        return match ($this) {
            self::Created, self::UserActivated => AuditCategory::Created,
            self::Updated, self::Restored, self::LevelChanged, self::PermissionsUpdated, self::SettingsUpdated, self::UserDeactivated => AuditCategory::Updated,
            self::Deleted, self::ForceDeleted => AuditCategory::Deleted,
            self::Login, self::Logout, self::LoginFailed, self::LoginLocked, self::PasswordChanged, self::PasswordReset, self::TwoFactorEnabled, self::TwoFactorDisabled, self::TwoFactorFailed, self::Registered => AuditCategory::Auth,
            default => AuditCategory::Other,
        };
    }

    /**
     * The fixed `log_name` of events that don't belong to a module.
     */
    public function logName(): ?string
    {
        return match ($this) {
            self::Login, self::Logout, self::LoginFailed, self::LoginLocked, self::TwoFactorEnabled, self::TwoFactorDisabled, self::TwoFactorFailed, self::Registered => 'auth',
            self::AuditPruned, self::AttachmentsSwept => 'system',
            default => null,
        };
    }
}
