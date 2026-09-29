<?php
require_once 'db.php';
require_once 'config.php';

$name = isset($_GET['fullname']) ? htmlspecialchars((string)$_GET['fullname']) : 'Valued Investor';
$teamsLink = isset($_GET['teams_link']) ? trim((string)$_GET['teams_link']) : '';

if ($teamsLink === '' || $teamsLink === '#') {
    $activeWebinar = $pdo->query("
        SELECT webinar_link FROM webinar_tbl
        WHERE is_published = 1
        LIMIT 1
    ")->fetch(PDO::FETCH_ASSOC);

    if (!$activeWebinar) {
        $activeWebinar = $pdo->query("
            SELECT webinar_link FROM webinar_tbl
            WHERE status IN ('active', 'upcoming')
            ORDER BY `schedule_date&time` ASC
            LIMIT 1
        ")->fetch(PDO::FETCH_ASSOC);
    }

    $teamsLink = $activeWebinar['webinar_link'] ?? '#';
}

$safeTeamsLink = filter_var($teamsLink, FILTER_VALIDATE_URL) ? $teamsLink : '#';
$step3Url = 'thankyou.php' . (isset($_GET['fullname']) ? ('?fullname=' . urlencode((string)$_GET['fullname'])) : '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Step 2 · Register With Teams</title>
    <link rel="icon" type="image/png" href="img/logo.png">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen p-5">
    <main class="max-w-2xl mx-auto py-8">
        <div class="bg-white border border-slate-200 rounded-3xl shadow-xl p-6 sm:p-10">
            <div class="text-center mb-8">
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-amber-100 text-amber-700 text-xs font-bold uppercase tracking-wider">
                    <i class="fas fa-hourglass-half"></i> Almost Done
                </div>
                <h1 class="text-3xl sm:text-4xl font-extrabold text-slate-900 mt-4 leading-tight">
                    Step 2: Complete Registration With Teams
                </h1>
                <p class="mt-3 text-slate-600 text-base sm:text-lg">
                    Hi <?php echo $name; ?>, to receive official webinar reminders from Microsoft Teams, complete one final registration step.
                </p>
            </div>

            <div class="bg-blue-50 border border-blue-100 rounded-2xl p-5 mb-8">
                <h2 class="font-bold text-slate-900 mb-2">What Happens Next</h2>
                <ul class="space-y-2 text-sm text-slate-700">
                    <li>1. Click the button below to open the Teams registration form.</li>
                    <li>2. Teams may ask for name and email to confirm attendance.</li>
                    <li>3. This page will automatically move to your confirmation page right after you click the button.</li>
                </ul>
            </div>

            <div class="flex flex-col gap-4">
                <a id="teamsRegisterBtn" href="<?php echo htmlspecialchars($safeTeamsLink); ?>" target="_blank" rel="noopener noreferrer" class="w-full bg-amber-400 hover:bg-amber-300 text-[#0f2b44] font-extrabold px-6 py-4 rounded-2xl text-center shadow-lg transition-all hover:-translate-y-0.5 inline-flex items-center justify-center gap-3">
                    <i class="fas fa-users"></i> Register With Teams
                </a>
            </div>
        </div>
    </main>
    <script>
        (function () {
            var btn = document.getElementById('teamsRegisterBtn');
            if (!btn) return;

            btn.addEventListener('click', function () {
                // Teams opens in a new tab via target="_blank".
                // Move this current tab to Step 3 after 10 seconds.
                setTimeout(function () {
                    window.location.href = <?php echo json_encode($step3Url); ?>;
                }, 10000);
            });
        })();
    </script>
</body>
</html>
