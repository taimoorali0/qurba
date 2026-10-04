<?php
// ===== QURBA admin: what is waiting for someone =====
namespace App\Filament\Widgets;

use App\Models\Adhkar;
use App\Models\ContentSource;
use App\Models\LearnInterest;
use Filament\Widgets\Widget;

class ReviewQueue extends Widget
{
    protected static string $view = 'filament.widgets.review-queue';
    protected static ?int $sort = 1;

    protected function getViewData(): array
    {
        return ['rows' => [
            ['Content sources to review', 'Text, translation, tafsir and audio licences', ContentSource::where('status', 'pending_review')->count(), url('/admin/content-sources'), 'heroicon-o-shield-check'],
            ['Adhkar in review', 'Waiting for a religious reviewer', Adhkar::where('status', 'in_review')->count(), url('/admin/adhkars'), 'heroicon-o-check-badge'],
            ['Adhkar drafts', 'Imported or being written', Adhkar::where('status', 'draft')->count(), url('/admin/adhkars'), 'heroicon-o-pencil-square'],
            ['New class requests', 'Families waiting to be contacted', LearnInterest::where('status', 'new')->count(), url('/admin/learn-interests'), 'heroicon-o-inbox-arrow-down'],
        ]];
    }
}
