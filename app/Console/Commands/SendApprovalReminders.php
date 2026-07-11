<?php

namespace App\Console\Commands;

use App\Services\ApprovalWorkflowNotifier;
use Illuminate\Console\Command;

class SendApprovalReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'approvals:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Nhắc nhở người kiểm tra/phê duyệt/ban hành khi còn 1 ngày đến hạn mong muốn hoàn thành';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Đang quét các bước phê duyệt sắp đến hạn...');
        $sent = ApprovalWorkflowNotifier::sendDueReminders();
        $this->info("Đã gửi {$sent} nhắc nhở.");
    }
}
