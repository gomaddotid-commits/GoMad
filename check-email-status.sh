#!/bin/bash
echo "╔══════════════════════════════════════════════════════╗"
echo "║     GoMad Email System Status                       ║"
echo "╚══════════════════════════════════════════════════════╝"
echo ""

echo "📧 Configuration:"
echo "  APP_ENV: $(grep APP_ENV .env | cut -d= -f2)"
echo "  QUEUE_CONNECTION: $(grep QUEUE_CONNECTION .env | cut -d= -f2)"
echo "  MAIL_MAILER: $(grep MAIL_MAILER .env | cut -d= -f2)"
echo ""

echo "📊 Database Jobs:"
php artisan tinker --execute="
if (Schema::hasTable('jobs')) {
    echo '  Pending jobs: ' . DB::table('jobs')->count() . PHP_EOL;
    echo '  Failed jobs: ' . DB::table('failed_jobs')->count() . PHP_EOL;
} else {
    echo '  Jobs table not found (using afterResponse mode)' . PHP_EOL;
}
"

echo ""
echo "📬 Recent Email Logs:"
tail -30 storage/logs/laravel.log | grep "📧" | tail -5

echo ""
echo "🔄 Queue Worker Status:"
if pgrep -f "queue:work" > /dev/null; then
    echo "  ✅ Queue worker RUNNING"
else
    echo "  ⚠️  Queue worker NOT RUNNING (using afterResponse fallback)"
fi
