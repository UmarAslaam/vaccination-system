<?php
// includes/schedule.php - Vaccination schedule ka dimaagh (auto-suggest)
// Bache ki DOB + vaccines ki list + uski bookings -> har vaccine ka status:
//   done     = lag chuki hai
//   booked   = appointment ban chuki hai (abhi lagni baqi)
//   due      = ab lagni chahiye (30 din tak ka waqt)
//   overdue  = 30 din se zyada late
//   upcoming = abhi umar nahi hui
// Ye file Day 10 (booking) mein bhi kaam aayegi.

require_once __DIR__ . '/functions.php';

// Aaj se kitne din baad (+) ya pehle (-) wo date hai
function days_until($date)
{
    $today = new DateTime('today');
    return (int)$today->diff(new DateTime($date))->format('%r%a');
}

// 0 -> "Today", 1 -> "Tomorrow", 5 -> "In 5 days"
function day_label($days)
{
    if ($days === 0) return 'Today';
    if ($days === 1) return 'Tomorrow';
    if ($days > 1)   return 'In ' . $days . ' days';
    return abs($days) . ' days ago';
}

// Din ko insaani zubaan mein: 3 -> "3 days", 30 -> "4 weeks", 120 -> "4 months"
function humanize_days($days)
{
    $days = abs((int)$days);
    if ($days < 14) return $days . ($days === 1 ? ' day' : ' days');
    if ($days < 60) return round($days / 7) . ' weeks';
    return round($days / 30) . ' months';
}

function vaccine_schedule($dob, array $vaccines, array $bookings)
{
    // Har vaccine ke liye is bache ki sab se achhi booking: vaccinated > pending > not_vaccinated
    $rank = ['vaccinated' => 3, 'pending' => 2, 'not_vaccinated' => 1];
    $best = [];
    foreach ($bookings as $b) {
        $vid   = $b['vaccine_id'];
        $score = $rank[$b['vaccination_status']] ?? 0;
        if (!isset($best[$vid]) || $score > ($rank[$best[$vid]['vaccination_status']] ?? 0)) {
            $best[$vid] = $b;
        }
    }

    $birth = new DateTime($dob);
    $rows  = [];

    foreach ($vaccines as $v) {
        // Wo tareekh jab ye vaccine lagni chahiye = DOB + recommended_age_days
        $due       = (clone $birth)->modify('+' . (int)$v['recommended_age_days'] . ' days');
        $daysToDue = days_until($due->format('Y-m-d'));      // + matlab aage, - matlab guzar chuki

        $b       = $best[$v['id']] ?? null;
        $bStatus = $b['vaccination_status'] ?? '';

        if ($bStatus === 'vaccinated')   { $status = 'done'; }
        elseif ($bStatus === 'pending')  { $status = 'booked'; }
        elseif ($daysToDue > 0)          { $status = 'upcoming'; }
        elseif ($daysToDue >= -30)       { $status = 'due'; }
        else                             { $status = 'overdue'; }

        $rows[] = [
            'vaccine'     => $v,
            'status'      => $status,
            'due_date'    => $due->format('Y-m-d'),
            'days_to_due' => $daysToDue,
            'booking'     => $b,
        ];
    }

    // Zaroori pehle: overdue, due, booked, upcoming, done (phir due date ke hisaab se)
    $order = ['overdue' => 0, 'due' => 1, 'booked' => 2, 'upcoming' => 3, 'done' => 4];
    usort($rows, function ($a, $b) use ($order) {
        return [$order[$a['status']], $a['due_date']] <=> [$order[$b['status']], $b['due_date']];
    });

    return $rows;
}

function schedule_badge($status)
{
    $map = [
        'done'     => ['success',   'Vaccinated'],
        'booked'   => ['info',      'Booked'],
        'due'      => ['warning',   'Due now'],
        'overdue'  => ['danger',    'Overdue'],
        'upcoming' => ['secondary', 'Upcoming'],
    ];
    [$color, $label] = $map[$status];
    return '<span class="badge rounded-pill text-bg-' . $color . '">' . $label . '</span>';
}

// Har row ke neeche chhota sa nota
function schedule_note($r)
{
    switch ($r['status']) {
        case 'done':
            if (($r['booking']['source'] ?? '') === 'past') {
                return 'Earlier vaccination (' . (!empty($r['booking']['verified']) ? 'verified' : 'awaiting verification') . ')';
            }
            return 'Completed';
        case 'booked':
            return 'Appointment on ' . format_date($r['booking']['appointment_date']);
        case 'upcoming':
            return 'Due ' . format_date($r['due_date']) . ' (in ' . humanize_days($r['days_to_due']) . ')';
        case 'due':
            return $r['days_to_due'] === 0
                ? 'Due today'
                : 'Due since ' . humanize_days($r['days_to_due']) . ' ago';
        default:
            return 'Was due on ' . format_date($r['due_date']);
    }
}

// Ek row ka HTML (list ke andar)
function schedule_item_html($r)
{
    $v    = $r['vaccine'];
    $html = '<li class="list-group-item d-flex justify-content-between align-items-start gap-2 px-0">';
    $html .= '<div class="min-w-0"><div class="fw-semibold">' . e($v['vaccine_name']) . '</div>';
    $html .= '<small class="text-muted">' . e(schedule_note($r)) . '</small>';
    if (in_array($r['status'], ['due', 'overdue'], true) && $v['availability'] !== 'available') {
        $html .= '<div><small class="text-danger">Vaccine currently unavailable</small></div>';
    }
    $html .= '</div><div class="text-nowrap">' . schedule_badge($r['status']) . '</div></li>';
    return $html;
}


// Parent ki darj ki hui purani vaccinations ko "booking jaisi" rows banata hai,
// taake vaccine_schedule() unhein bina kisi tabdeeli ke "done" gine.
// Rejected records nazar-andaz. Return: [child_id => [rows...]]
function past_records_by_child(PDO $pdo, array $childIds)
{
    $out = [];
    if (!$childIds) {
        return $out;
    }
    $in   = implode(',', array_fill(0, count($childIds), '?'));   // sirf ? ki ginti, koi user ka text nahi
    $stmt = $pdo->prepare(
        "SELECT child_id, vaccine_id, given_date, status
         FROM past_vaccinations
         WHERE status <> 'rejected' AND child_id IN ($in)"
    );
    $stmt->execute(array_values($childIds));
    foreach ($stmt->fetchAll() as $p) {
        $out[$p['child_id']][] = [
            'child_id'           => $p['child_id'],
            'vaccine_id'         => $p['vaccine_id'],
            'approval_status'    => 'approved',
            'vaccination_status' => 'vaccinated',
            'appointment_date'   => $p['given_date'],
            'source'             => 'past',
            'verified'           => ($p['status'] === 'verified'),
        ];
    }
    return $out;
}
