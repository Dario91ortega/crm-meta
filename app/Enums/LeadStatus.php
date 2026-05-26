<?php

namespace App\Enums;

/**
 * Lifecycle states of a Lead.
 *
 * Pending    → freshly created, waiting for ProcessLead job to pick it up.
 * Processing → ProcessLead job is currently working on the lead.
 * Processed  → ProcessLead resolved a contact (existing or newly created).
 * Failed     → ProcessLead errored; details in activity log.
 * Skipped    → payload had neither email nor phone, could not dedupe.
 */
enum LeadStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Processed = 'processed';
    case Failed = 'failed';
    case Skipped = 'skipped';
}
