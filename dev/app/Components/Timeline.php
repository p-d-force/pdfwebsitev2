<?php declare(strict_types=1);
namespace App\Components;

/**
 * Timeline component — vertical event timeline with color-coded dots.
 *
 * Usage:
 *   $timeline = new Timeline($events);
 *   echo $timeline->render();
 *
 * Each event must have: event_date, event_type, event_description (optional), actor (optional).
 */
class Timeline
{
    private const DOT_COLORS = [
        'filed'                    => '#60a5fa',
        'acknowledged'             => '#22c55e',
        'accepted'                 => '#22c55e',
        'assigned'                 => '#22c55e',
        'extension_requested'      => '#f59e0b',
        'extension_granted'        => '#f59e0b',
        'investigation_opened'     => '#f59e0b',
        'interview_conducted'      => '#f59e0b',
        'site_visit'               => '#f59e0b',
        'preliminary_findings'     => '#f59e0b',
        'district_response'        => '#f59e0b',
        'findings_issued'          => '#ef4444',
        'corrective_action_ordered'=> '#22c55e',
        'compliance_verified'      => '#22c55e',
        'closed'                   => '#767676',
        'appealed'                 => '#a78bfa',
        'reopened'                 => '#ec4899',
    ];

    public function __construct(
        private array $events,
        private string $emptyMessage = 'No events recorded.'
    ) {}

    public function render(): string
    {
        if (empty($this->events)) {
            return '<div class="empty-state"><p>' . h($this->emptyMessage) . '</p></div>';
        }

        $html = '<div class="timeline">';
        $html .= '<div class="timeline-line"></div>';

        $count = count($this->events);
        for ($i = 0; $i < $count; $i++) {
            $e = $this->events[$i];
            $type  = $e['event_type'] ?? '';
            $dotColor = self::DOT_COLORS[$type] ?? '#767676';
            $label = ucwords(str_replace('_', ' ', $type));

            $html .= '<div class="timeline-event">';
            $html .= '<div class="timeline-dot" style="background:' . $dotColor . ';box-shadow:0 0 10px ' . $dotColor . '44;"></div>';
            $html .= '<div class="timeline-card">';
            $html .= '<div class="timeline-date">' . format_date($e['event_date']) . '</div>';
            $html .= '<span class="timeline-type-badge" style="background:' . $dotColor . '1a;color:' . $dotColor . ';border:1px solid ' . $dotColor . '44;">' . h($label) . '</span>';

            if (!empty($e['event_description'])) {
                $html .= '<p class="timeline-desc">' . h($e['event_description']) . '</p>';
            }
            if (!empty($e['actor'])) {
                $html .= '<span class="timeline-actor">&mdash; ' . h($e['actor']) . '</span>';
            }

            $html .= '</div></div>';

            // Gap detection: if next event is > 30 days later, show dormant period
            if ($i < $count - 1) {
                $currDate = new \DateTime($e['event_date'] ?? 'now');
                $nextDate = new \DateTime($this->events[$i + 1]['event_date'] ?? 'now');
                $gap = (int)$currDate->diff($nextDate)->days;
                if ($gap > 30) {
                    $html .= '<div class="timeline-gap">';
                    $html .= '<div class="timeline-gap-line"></div>';
                    $html .= '<span class="timeline-gap-label">' . $gap . ' days inactive</span>';
                    $html .= '</div>';
                }
            }
        }

        $html .= '</div>';
        return $html;
    }
}
