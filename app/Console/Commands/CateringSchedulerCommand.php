<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Models\Setting;
use App\Mail\CateringNotificationMail;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class CateringSchedulerCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'catering:schedule-process';

    /**
     * The console command description.
     */
    protected $description = 'Process automated daily catering reminders, grace period warnings, auto-cancellations, and self-pickup notices.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting SmartServe Catering schedule processor...');
        
        $policyDays = (int) Setting::getVal('cancellation_policy_days', 7);
        $graceDays = (int) Setting::getVal('grace_period_days', 2);
        
        $today = Carbon::today();
        
        // 1. Process Unpaid Balance Reminders & Auto-cancellations
        $unpaidOrders = Order::whereIn('status', ['Confirmed', 'Balance Rejected'])->get();
        
        foreach ($unpaidOrders as $order) {
            $deliveryDate = Carbon::parse($order->delivery_date);
            $diffDays = $today->diffInDays($deliveryDate, false);
            
            // 3 days before balance due
            if ($diffDays === ($policyDays + 3)) {
                $this->sendThreeDayReminder($order);
            }
            // Exactly on balance due date
            elseif ($diffDays === $policyDays) {
                $this->sendDueDateReminder($order);
            }
            // Within grace period
            elseif ($diffDays < $policyDays && $diffDays >= ($policyDays - $graceDays)) {
                $this->sendGraceWarning($order, $policyDays, $graceDays, $diffDays);
            }
            // Past grace period
            elseif ($diffDays < ($policyDays - $graceDays)) {
                $this->autoCancelOrder($order);
            }
        }
        
        // 2. Process Self-Pickup Reminders (events scheduled for tomorrow)
        $tomorrow = Carbon::tomorrow()->toDateString();
        $pickupOrders = Order::where('delivery_date', $tomorrow)
            ->whereIn('status', ['Confirmed', 'Payment Submitted', 'Completed'])
            ->where(function($q) {
                $q->where('delivery_address', 'like', '%pickup%')
                  ->orWhere('delivery_address', 'like', '%Self-Pickup%');
            })
            ->get();
            
        foreach ($pickupOrders as $order) {
            $this->sendPickupReminder($order);
        }
        
        $this->info('SmartServe Catering scheduler process completed successfully.');
    }

    private function sendThreeDayReminder(Order $order)
    {
        $user = $order->user;
        $balance = (float)$order->total_price * 0.7;
        
        $subject = "Peringatan Pembayaran Baki: Pakej {$order->package_name}";
        $greeting = "Salam mesra {$user->full_name},";
        $lines = [
            "Ini adalah peringatan bahawa baki bayaran katering sebanyak 70% (RM " . number_format($balance, 2) . ") bagi tempahan #{$order->id} akan tamat tempoh dalam masa 3 hari.",
            "Tarikh Acara: {$order->delivery_date}",
            "Sila jelaskan pembayaran baki secepat mungkin untuk mengelakkan sebarang kesulitan."
        ];
        
        // Send Email
        Mail::to($user->email)->send(new CateringNotificationMail(
            $subject, $greeting, $lines, "Muat Naik Resit Baki", route('orders.show', ['id' => $order->id])
        ));
        
        // Send WhatsApp
        $waMsg = "Hai {$user->full_name}, baki bayaran katering sebanyak 70% (RM " . number_format($balance, 2) . ") bagi tempahan #{$order->id} akan tamat tempoh dalam 3 hari. Sila jelaskan pembayaran baki secepat mungkin. Terima kasih.";
        WhatsAppService::send($user->phone, $waMsg);
    }

    private function sendDueDateReminder(Order $order)
    {
        $user = $order->user;
        $balance = (float)$order->total_price * 0.7;
        
        $subject = "Baki Pembayaran Kini Matang: Pakej {$order->package_name}";
        $greeting = "Salam mesra {$user->full_name},";
        $lines = [
            "Peringatan: Baki bayaran katering 70% sebanyak RM " . number_format($balance, 2) . " bagi tempahan #{$order->id} telah matang hari ini.",
            "Tarikh Acara: {$order->delivery_date}",
            "Sila muat naik slip pembayaran di portal pelanggan hari ini untuk mengelakkan tempahan anda dibatalkan secara automatik."
        ];
        
        Mail::to($user->email)->send(new CateringNotificationMail(
            $subject, $greeting, $lines, "Urus Bayaran Baki", route('orders.show', ['id' => $order->id])
        ));
        
        $waMsg = "Hai {$user->full_name}, baki bayaran katering 70% (RM " . number_format($balance, 2) . ") bagi tempahan #{$order->id} matang HARI INI. Sila muat naik slip pembayaran di portal secepat mungkin untuk mengelakkan pembatalan automatik.";
        WhatsAppService::send($user->phone, $waMsg);
    }

    private function sendGraceWarning(Order $order, int $policyDays, int $graceDays, int $diffDays)
    {
        $user = $order->user;
        $balance = (float)$order->total_price * 0.7;
        $remainingDays = $diffDays - ($policyDays - $graceDays);
        
        $subject = "Amaran: Tempahan #{$order->id} Menghampiri Pembatalan Automatik";
        $greeting = "Salam mesra {$user->full_name},";
        $lines = [
            "Tempahan katering anda #{$order->id} kini berada dalam tempoh ehsan (grace period) pembayaran baki.",
            "Baki bayaran: RM " . number_format($balance, 2),
            "Sila ambil perhatian: Tempahan akan dibatalkan secara automatik oleh sistem dalam masa {$remainingDays} hari sekiranya slip pembayaran baki masih tidak dimuat naik."
        ];
        
        Mail::to($user->email)->send(new CateringNotificationMail(
            $subject, $greeting, $lines, "Muat Naik Slip Pembayaran", route('orders.show', ['id' => $order->id])
        ));
        
        $waMsg = "AMARAN: Tempahan katering #{$order->id} menghampiri pembatalan automatik. Anda mempunyai baki bayaran RM " . number_format($balance, 2) . ". Sila selesaikan dalam masa {$remainingDays} hari. Terima kasih.";
        WhatsAppService::send($user->phone, $waMsg);
    }

    private function autoCancelOrder(Order $order)
    {
        $user = $order->user;
        
        $order->status = 'Cancelled';
        $order->cancelled_by = 'admin'; // mapping to enum 'admin'
        $order->cancelled_at = now();
        $order->admin_note = "Auto-cancelled by system scheduler due to unpaid balance past due date and grace period.";
        $order->save();
        
        $subject = "Dibatalkan Automatik: Tempahan #{$order->id} Telah Dibatalkan";
        $greeting = "Salam sejahtera {$user->full_name},";
        $lines = [
            "Sistem telah membatalkan tempahan katering #{$order->id} secara automatik kerana baki bayaran 70% gagal diselesaikan selepas tempoh ehsan.",
            "Merujuk polisi tempahan kami, deposit 30% yang telah dibayar adalah hangus dan tidak akan dikembalikan.",
            "Sila hubungi pentadbir sekiranya terdapat sebarang pertanyaan."
        ];
        
        Mail::to($user->email)->send(new CateringNotificationMail(
            $subject, $greeting, $lines, "Urus Tempahan", route('orders.show', ['id' => $order->id])
        ));
        
        $waMsg = "TEMPAHAN DIBATALKAN: Tempahan katering #{$order->id} telah dibatalkan secara automatik oleh sistem kerana kegagalan menyelesaikan baki bayaran. Deposit 30% adalah hangus mengikut polisi. Terima kasih.";
        WhatsAppService::send($user->phone, $waMsg);
    }

    private function sendPickupReminder(Order $order)
    {
        $user = $order->user;
        
        $subject = "Peringatan: Ambil Sendiri (Self-Pickup) Esok!";
        $greeting = "Salam mesra {$user->full_name},";
        $lines = [
            "Ini adalah peringatan untuk tempahan katering anda #{$order->id} yang dijadualkan untuk Ambil Sendiri (Self-Pickup) esok.",
            "Tarikh Ambil: {$order->delivery_date}",
            "Masa: {$order->delivery_time}",
            "Lokasi Ambil: Gong Badak, Kuala Terengganu (Alamat penuh boleh didapati dalam invois anda).",
            "Sila pastikan anda membawa kenderaan yang bersesuaian dengan kuantiti tempangan katering anda."
        ];
        
        Mail::to($user->email)->send(new CateringNotificationMail(
            $subject, $greeting, $lines, "Lihat Invois Ambil", route('orders.show', ['id' => $order->id])
        ));
        
        $waMsg = "Peringatan: Tempahan katering #{$order->id} untuk Ambil Sendiri (Self-Pickup) esok jam {$order->delivery_time} di Gong Badak. Sila bawa kenderaan bersesuaian. Terima kasih.";
        WhatsAppService::send($user->phone, $waMsg);
    }
}
