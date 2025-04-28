<?php

namespace App\Http\Controllers\API\V1\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Enums\NotificationType;
use App\Models\FirebaseTokens;
use App\Models\Order;
use App\Models\Payment;
use App\Models\StripeSetting;
use App\Notifications\GuestRequestNotification;
use Illuminate\Support\Facades\Auth;
use Stripe\PaymentIntent;
use App\Helpers\Helper;
use App\Models\Booking;
use App\Models\Hotel;
use App\Models\User;
use Stripe\Exception\ApiErrorException;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\Webhook;
use UnexpectedValueException;
use Stripe\Account;
use Stripe\AccountLink;
use Stripe\Exception\SignatureVerificationException;
class StripePaymentController extends Controller
{
    public function createPaymentIntent(Request $request)
    {
        $validateData = $request->validate([
            'booking_unique_id' => 'required|exists:bookings,unique_id',
            // 'amount'     => 'required|numeric',
        ]);
        try {
            // user id
            $user_id = Auth::id();


            // Check if the order is found
            $booking = Booking::where('user_id', $user_id)
                ->where('unique_id', $validateData['booking_unique_id'])
                ->whereNotIn('status', ['cancelled', 'completed', 'close'])
                ->whereDoesntHave('payment', function ($query) {
                    $query->where('status', 'paid');
                })
                ->first();
            // dd($booking);
            if (!$booking) {
                return Helper::jsonErrorResponse('Bookings not found', 404);
            }

            // Check if the order already has a completed payment
            if ($booking->payment && $booking->payment->isPaid()) {
                return Helper::jsonErrorResponse('This bokking has already been paid.', 400);
            }

            // check this hotel set payment configarations
            $stripe_secret = Stripe::setApiKey(config('services.stripe.key'));

            if (!$stripe_secret) {
                return Helper::jsonErrorResponse('Payment facilities are not configured. Please contact support.', 404);
            }

            // Stripe::setApiKey(Crypt::decryptString($stripe_secret->stripe_secret_key));

            //calculation
            $amount = $booking->total_price * 100; // total amount in cents
            $transactionId = substr(uniqid('txn_booking', true), 0, 15);

            // Create a payment intent with the calculated amount and metadata
            $paymentIntent = PaymentIntent::create([
                'amount' => $amount,
                'currency' => 'usd',
                'metadata' => [
                    'user_id' => $booking->user_id,
                    'unique_id' => $validateData['booking_unique_id'],
                    'booking_id' => $booking->id,
                    'transaction_id' => $transactionId,
                    'payment_method' => 'online',
                ],
            ]);

            return Helper::jsonResponse(true, 'Payment Intent created successfully.', 200, [
                'client_secret' => $paymentIntent->client_secret,
            ]);
        } catch (ApiErrorException $e) {
            return Helper::jsonResponse(false, 'Stripe API error: ' . $e->getMessage(), 500);
        } catch (Exception $e) {
            return Helper::jsonResponse(false, 'General error: ' . $e->getMessage(), 500);
        }
    }

    public function handleWebhook(Request $request, $slug): JsonResponse
    {

        $stripe_secret = Stripe::setApiKey(config('services.stripe.key'));

        $stripe_webhook_secret = Stripe::setApiKey(config('services.stripe.webhook_secret'));

        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = $stripe_webhook_secret;

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
        } catch (UnexpectedValueException $e) {
            return Helper::jsonResponse(false, $e->getMessage(), 400, []);
        } catch (SignatureVerificationException $e) {
            return Helper::jsonResponse(false, $e->getMessage(), 400, []);
        }

        //? Handle the event based on its type
        try {
            switch ($event->type) {
                case 'payment_intent.succeeded':
                    $this->handlePaymentSuccess($event->data->object);
                    return Helper::jsonResponse(true, 'Payment successful', 200, []);

                case 'payment_intent.payment_failed':
                    $this->handlePaymentFailure($event->data->object);
                    return Helper::jsonResponse(true, 'Payment failed', 200, []);

                default:
                    return Helper::jsonResponse(true, 'Unhandled event type', 200, []);
            }
        } catch (Exception $e) {
            return Helper::jsonResponse(false, $e->getMessage(), 500, []);
        }
    }

    protected function handlePaymentSuccess($paymentIntent): void
    {
        //* Record the successful payment in the database
        $payment = Payment::create([
            'booking_id' => $paymentIntent->metadata->booking_id,
            'user_id' => $paymentIntent->metadata->user_id,
            'transaction_id' => $paymentIntent->metadata->transaction_id,
            'transaction_number' => $paymentIntent->metadata->transaction_number,
            'payment_method' => $paymentIntent->metadata->payment_method,
            'payment_id' => $paymentIntent->metadata->payment_id,
            'amount' => $paymentIntent->metadata->amount,
            'status' => 'paid',
        ]);

        $user = User::find($paymentIntent->metadata->user_id);

    }

    protected function handlePaymentFailure($paymentIntent): void
    {
        Log::info("Payment failed: " . $paymentIntent);
        //* Record the failure payment in the database
        $payment = Payment::create([
            'user_id' => $paymentIntent->metadata->user_id,
            'order_id' => $paymentIntent->metadata->order_id,
            'hotel_id' => $paymentIntent->metadata->hotel_id,
            'amount' => $paymentIntent->amount / 100,
            'transaction_id' => $paymentIntent->metadata->transaction_id,
            'payment_method' => $paymentIntent->metadata->payment_method,
            'status' => 'unpaid',
        ]);


        // $guest = User::find($paymentIntent->metadata->user_id);
        // $notificationData = [
        //     'message' => 'Payment is unsuccessful',
        //     // 'url' => $paymentIntent->metadata->order_id ?? '',
        //     'url' => '',
        //     'type' => NotificationType::PAYMENT,
        //     'thumbnail' => ''
        // ];
        // // notify guest 
        // $guest->notify(new GuestRequestNotification($notificationData));
        // $firebaseTokens = FirebaseTokens::where('user_id', $guest->id)->get();

        // // Now you have a collection, you can check if the collection is not empty and then get the tokens
        // if (!empty($firebaseTokens)) {

        //     $notifyData = [
        //         'title' => 'Payment is unsuccessful',
        //         'body' => $paymentIntent->metadata->order_id
        //     ];
        //     foreach ($firebaseTokens as $tokens) {
        //         if (!empty($tokens->token)) {
        //             $token = $tokens->token; // Pluck tokens into an array
        //             // Send notifications using the token array
        //             Helper::sendNotifyMobile($token, $notifyData);
        //         } else {
        //             Log::warning('Token is missing for user: ' . $guest->id);
        //         }
        //     }
        // } else {
        //     Log::warning('No Firebase tokens found for this user.');
        // }
    }


}
