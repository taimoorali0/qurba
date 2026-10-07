<?php

use App\Models\ContentEntry;
use App\Models\ContentSource;

function libraryEntry(): ContentEntry
{
    $source = ContentSource::create(['name' => 'Test source', 'type' => 'hadith', 'status' => 'approved', 'redistribution_allowed' => true]);
    return ContentEntry::create(['module' => 'hadith', 'slug' => 'test-entry', 'title' => ['en' => 'Test'],
        'body' => ['en' => 'Fixture only'], 'status' => 'published', 'content_source_id' => $source->id]);
}

it('hides drafts and content whose source is not approved for redistribution', function () {
    $entry = libraryEntry();
    $this->getJson('/api/v1/library/hadith/test-entry')->assertOk();
    $entry->update(['status' => 'draft']);
    $this->getJson('/api/v1/library/hadith/test-entry')->assertNotFound();
    $entry->update(['status' => 'published']);
    $entry->source->update(['redistribution_allowed' => false]);
    $this->getJson('/api/v1/library/hadith')->assertJsonCount(0, 'data');
    $entry->source->update(['redistribution_allowed' => true, 'status' => 'pending_review']);
    $this->getJson('/api/v1/library/hadith/test-entry')->assertNotFound();
});

it('serves only published permitted recordings with their offline permission', function () {
    $entry = libraryEntry();
    $entry->recordings()->create(['content_source_id' => $entry->content_source_id, 'language_code' => 'en',
        'speaker' => 'Test reader', 'url' => 'https://example.com/test.mp3', 'status' => 'published']);
    $entry->recordings()->create(['content_source_id' => $entry->content_source_id, 'language_code' => 'ur',
        'speaker' => 'Draft reader', 'url' => 'https://example.com/draft.mp3', 'status' => 'draft']);
    $this->getJson('/api/v1/library/hadith/test-entry')->assertJsonCount(1, 'data.audio')
        ->assertJsonPath('data.audio.0.offline_allowed', false);
});

it('rejects unknown modules and validates collection filters', function () {
    $this->getJson('/api/v1/library/unknown')->assertNotFound();
    $this->getJson('/api/v1/library/hadith?collection[]=bad')->assertUnprocessable();
});
