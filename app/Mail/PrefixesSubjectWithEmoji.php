<?php

namespace App\Mail;

/**
 * Single place where the emoji carried by an outgoing subject line is defined.
 *
 * Translations stay emoji-free: every mailable builds its subject as emoji plus
 * the translated text, so the eleven locale files cannot drift from each other.
 */
trait PrefixesSubjectWithEmoji
{
    /** Brand emoji (U+1F510) prefixing every user-facing mail. */
    public const string BRAND_EMOJI = '🔐';

    /** Severity emoji (U+26A0 U+FE0F) of a warning-level operator alert. */
    public const string WARNING_EMOJI = '⚠️';

    /** Severity emoji (U+1F6A8) of a critical-level operator alert. */
    public const string CRITICAL_EMOJI = '🚨';

    private function emojiSubject(string $emoji, string $subject): string
    {
        return $emoji.' '.$subject;
    }
}
