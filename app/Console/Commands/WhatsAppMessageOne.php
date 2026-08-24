<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\WhatsAppNumber;
class WhatsAppMessageOne extends Command
{
    protected $signature = 'whats:messageone';
    protected $description = 'Send a WhatsApp message';

    public function handle()
    {
        $contact = WhatsAppNumber::whereNull('status')
            ->orderBy('id', 'desc')
            ->first(); // Get the first contact

        if ($contact) {
            $mobileNumber = $this->formatMobileNumber($contact->Mobile);
            $message = "
Phone pe- 
Google pay-
Paytm-       7042759541

Helpline Number:-   9643859339

घर बैठके गेम लगाये और अपना भुगतान तुरन्त अपने वॉलेट में पाए गेम खेलने के लिए नीचे ब्लू लाइन पर क्लिक करे और एप्प डाउनलोड करे  |  

Rate.     Jodi.  10 /  950

               Hurup  10 / 95
....

👇🏻👇🏻👇🏻👇🏻👇🏻👇🏻👇🏻👇🏻
www.sattalives.com

और अपने दोस्तों के साथ शेयर करे बेटिंग का 5% कमीशन ले लाइफ टाइम

👇👇👇👇👇👇👇
www.sattalives.com
अभी आप घर बैठकर ऑनलाइन सट्टा खेलकर कमा सकते हैं अब आपको कहीं जाने की जरूरत नहीं है आपके ही मोबाइल में हम आपके लिए लाए हैं ऑनलाइन सट्टा है इसे ज्वाइन करने के लिए डाउनलोड बटन पर क्लिक करे 

Get Mobile App click to download button
👇👇👇👇👇👇
www.sattalives.com";

            $response = $this->sendMessage($mobileNumber, $message);
            Log::info('Response from API for mobile number ' . $mobileNumber . ': ', $response);
            if ($response['success'] && isset($response['data']['message_status']) && $response['data']['message_status'] === 'Success') {
                Log::info('Message sent successfully to: ' . $mobileNumber);
                $this->info('Message sent successfully to: ' . $mobileNumber); // Print success message to the console
                $contact->status = 1; // Sent
                $contact->message_sent = 1;
            } else {
                Log::error('Failed to send message to: ' . $mobileNumber . ' - ' . ($response['error'] ?? 'Unknown error'));
                $contact->status = 0; // Failed
                $contact->message_sent = 0;
            }
            $contact->save();

            // Wait for 3 minutes (180 seconds) before sending the next message
            sleep(180); // 3 minutes
        } else {
            $this->info('No contacts available to send messages.');
        }

        return 0;
    }

    private function sendMessage($to, $message)
    {
        $curl = curl_init();

        $postData = [
            'appkey' => 'b38e172a-aab7-4229-a9ce-8d0586b9627f',
            'authkey' => 'mWqMjJq3o7HPwd7dElCdt7kjAiQwfPvjrgrukkCucExNVXYHJF',
            'to' => $to,
            'message' => $message,
        ];

        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://whats-api.rcsoft.in/api/create-message',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($postData),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
            ],
            CURLOPT_TIMEOUT => 30, // Set timeout to 30 seconds
        ]);

        $response = curl_exec($curl);
        if ($error = curl_error($curl)) {
            Log::error('cURL Error: ' . $error);
            return ['success' => false, 'error' => $error];
        }

        curl_close($curl);
        $responseData = json_decode($response, true);

        // Log full API response for debugging
        Log::info('Full response from API for mobile number ' . $to . ': ' . json_encode($responseData));

        if (is_null($responseData)) {
            Log::error('Failed to decode JSON response: ' . $response);
            return ['success' => false, 'error' => 'Failed to decode JSON response'];
        }

        return [
            'success' => isset($responseData['message_status']) && $responseData['message_status'] === 'Success',
            'data' => $responseData,
            'error' => $responseData['error'] ?? 'Unknown error',
        ];
    }

    private function formatMobileNumber($mobileNumber)
    {
        $mobileNumber = preg_replace('/\s+/', '', $mobileNumber);
        return substr($mobileNumber, 0, 2) !== '91' ? '91' . $mobileNumber : $mobileNumber;
    }
}
