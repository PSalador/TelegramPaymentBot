<?php

namespace App\Http\Controllers\Backend;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Telegram\Bot\Keyboard\Keyboard;
use Telegram\Bot\Api;
use Telegram;
use App\Product;
use App\Order;
use App\TelegramUser;
use App\Setting;
use Mail;

class NoticeController extends Controller
{

  /**
   * Notice admin
   */
//   public static function vebinar_paid(Order $order)
//   {

//     $url   = \URL::to('/') . '/admin/order/' . $order->id . '/edit';
//     $email = Setting::getSettings('admin_email');
//     $email = ($email) ? $email->value : 0;
//     $data  = [
//       'url' => $url
//     ];

//     \Log::info('email');
//     \Log::info(print_r($email, true));

//     Mail::send('emails.welcome', $data, function ($message) use ($email) {
//       $message->to($email);
//     });
//   }

  /**
   * Notice users when subscription expires
   */
//   public static function subscription_expires() {

//     $telegram = new Api(Telegram::getAccessToken());
//     $now      = new \DateTime();
//     $last_pay = $now->modify('-28 day');
//     $last_pay = $last_pay->format('Y-m-d');
//     $users    = TelegramUser::whereDate('subscribe_date', '=', $last_pay)->where('in_chat', '=', 1)->where('sub_notice', '=', 0)->take(5)->get();

//     foreach($users as $user) {

//       $user->sub_notice = 1;
//       $user->save();

//       $reply_markup = new Keyboard();
//       $reply_markup->inline();

//       $subscription_cost = Setting::getSettings('subscription_cost');
//       $subscription_cost = ($subscription_cost) ? $subscription_cost->value : 0;
//       $merchant_id       = Setting::getSettings('merchant_id');
//       $merchant_id       = ($merchant_id) ? $merchant_id->value : 0;
//       $secret_word       = 'ignetdapassion';
//       $sign              = md5($merchant_id.':'.$subscription_cost.':'.$secret_word.':'.$user->id);

//       $text  = sprintf('%s' . PHP_EOL, 'Срок действия вашей подписки истекает через три дня');
//       $text .= sprintf('%s' . PHP_EOL, 'Стоимость: ' . $subscription_cost . ' руб/мес');
//       $url   = 'http://www.free-kassa.ru/merchant/cash.php?' . 'm=' . $merchant_id . '&oa=' . $subscription_cost . '&o=' . $user->id . '&s=' . $sign . '&us_type=subscribe';

//       $sub_btn = Keyboard::inlineButton([
//         'text' => 'Оплатить',
//         'url'  => $url,
//       ]);

//       $reply_markup->row(
//         $sub_btn
//       );

//       $telegram->sendMessage([
//         'chat_id'      => $user->id,
//         'text'         => $text,
//         'reply_markup' => $reply_markup
//       ]);
//     }
//   }

  /**
   * Notice users when subscription expired
   */
  public static function subscription_expired_week($days, $tarif) {

    $telegram = new Api(Telegram::getAccessToken());
    $now      = new \DateTime();
    $last_pay = $now->modify('-'.$days.' day');
    $last_pay = $last_pay->format('Y-m-d');
    $users    = TelegramUser::whereDate('subscribe_date', '<', $last_pay)
                ->where([
                    ['in_chat', '=', 1],
                    ['tarif', '=', $tarif]
                ])
                ->take(5)
                ->get();

    foreach($users as $user) {

        $subscription_cost_week = Setting::getSettings('subscription_cost_week');
        $subscription_cost_week = ($subscription_cost_week) ? $subscription_cost_week->value : 0;
        $subscription_cost_month = Setting::getSettings('subscription_cost_month');
        $subscription_cost_month = ($subscription_cost_month) ? $subscription_cost_month->value : 0;
        $subscription_cost_year = Setting::getSettings('subscription_cost_year');
        $subscription_cost_year = ($subscription_cost_year) ? $subscription_cost_year->value : 0;

        $chat_id           = Setting::getSettings('chat_id');
      $chat_id           = ($chat_id) ? $chat_id->value : 0;

      if ( ! $chat_id) {
        return;
      }

      $user->in_chat = 0;
      $user->save();

      $merchant_id       = Setting::getSettings('merchant_id');
      $merchant_id       = ($merchant_id) ? $merchant_id->value : 0;
      $secret_word       = '0uv1cxfe';
      $sign_week              = md5($merchant_id.':'.$subscription_cost_week.':'.$secret_word.':'.$user->id);
      $sign_month              = md5($merchant_id.':'.$subscription_cost_month.':'.$secret_word.':'.$user->id);
      $sign_year              = md5($merchant_id.':'.$subscription_cost_year.':'.$secret_word.':'.$user->id);

      $text  = sprintf('%s' . PHP_EOL, 'Срок действия вашей подписки истек. Вы были удалены из чата.');
      $text .= sprintf('%s' . PHP_EOL, 'Чтобы вступить в чат оплатите подписку.');
      $text .= sprintf('%s' . PHP_EOL, 'Стоимость недельной подписки: ' . $subscription_cost_week . ' руб/мес');
      $text .= sprintf('%s' . PHP_EOL, 'Стоимость месячной подписки: ' . $subscription_cost_month . ' руб/мес');
      $text .= sprintf('%s' . PHP_EOL, 'Стоимость годовой подписки: ' . $subscription_cost_year . ' руб/мес');

      $url_week   = 'http://www.free-kassa.ru/merchant/cash.php?' . 'm=' . $merchant_id . '&oa=' . $subscription_cost_week . '&o=' . $user->id . '&s=' . $sign . '&us_type=subscribe&us_rate=week';
      $url_month   = 'http://www.free-kassa.ru/merchant/cash.php?' . 'm=' . $merchant_id . '&oa=' . $subscription_cost_month . '&o=' . $user->id . '&s=' . $sign . '&us_type=subscribe&us_rate=month';
      $url_year   = 'http://www.free-kassa.ru/merchant/cash.php?' . 'm=' . $merchant_id . '&oa=' . $subscription_cost_year . '&o=' . $user->id . '&s=' . $sign . '&us_type=subscribe&us_rate=year';



      $telegram->kickChatMember([
        'chat_id' => $chat_id,
        'user_id' => $user->id
      ]);

      $reply_markup = new Keyboard();
      $reply_markup->inline();

      $sub_btn_week = Keyboard::inlineButton([
        'text' => 'Оплатить на неделю',
        'url'  => $url_week,
      ]);
      $sub_btn_month = Keyboard::inlineButton([
        'text' => 'Оплатить на месяц',
        'url'  => $url_month,
      ]);
      $sub_btn_year = Keyboard::inlineButton([
        'text' => 'Оплатить на год',
        'url'  => $url_year,
      ]);

      $reply_markup->row(
        $sub_btn_week,
        $sub_btn_month,
        $sub_btn_year
      );

      $telegram->sendMessage([
        'chat_id'      => $user->id,
        'text'         => $text,
        'reply_markup' => $reply_markup
      ]);
    }
  }

  /**
   * Notice users when subscription paid successfully
   */
  public static function subscription_paid($user) {

    $telegram  = new Api(Telegram::getAccessToken());
    $chat_id   = Setting::getSettings('chat_id');
    $chat_id   = ($chat_id) ? $chat_id->value : 0;
    // $telegram  = new Api(env('TELEGRAM_BOT_TOKEN'));
    $chat_link = Setting::getSettings('chat_link');
    $chat_link = ($chat_link) ? $chat_link->value : 0;
    $text      = 'Вы успешно оплатили подписку.' . PHP_EOL;

    if ( ! $user->in_chat) {

      $telegram->unbanChatMember([
        'chat_id' => $chat_id,
        'user_id' => $user->id
      ]);

      $text .= 'Ссылка на вступление в чат: ' . $chat_link . PHP_EOL;
    }

    $telegram->sendMessage([
      'chat_id'      => $user->id,
      'text'         => $text
    ]);

//     $text = 'Выберите действие';

//     $vebinar_buy = Keyboard::inlineButton([
//       'text'          => 'Купить вебинары',
//       'callback_data' => 'buy_vebinars',
//     ]);

//     $my_vebinars = Keyboard::inlineButton([
//       'text'          => 'Мои покупки',
//       'callback_data' => 'my_vebinars',
//     ]);

//     $reply_markup = new Keyboard();
//     $reply_markup->inline();
//     $reply_markup->row(
//       $vebinar_buy,
//       $my_vebinars
//     );

//     $response = $telegram->sendMessage([
//       'chat_id'      => $query->getFrom()->getId(),
//       'text'         => $text,
//       'reply_markup' => $reply_markup
//     ]);
//   }

//   /**
//    * Send vebinar link to user
//    */
//   public function notice_user(Order $order) {

//     $telegram = new Api(env('TELEGRAM_BOT_TOKEN'));
//     $products = unserialize($order->products);
//     $product  = $products[0];
//     $vebinar  = Product::find($product);

//     $text  = 'Вы оплатили вебинар "' . $vebinar->title .'"' . PHP_EOL;
//     $text .= 'Ссылка на вебинар: ' . $order->description .'"' . PHP_EOL;

//     $telegram->sendMessage([
//       'chat_id' => $order->telegram_user_id,
//       'text'    => $text
//     ]);

//     $text = 'Выберите действие';

//     $vebinar_buy = Keyboard::inlineButton([
//       'text'          => 'Купить вебинары',
//       'callback_data' => 'buy_vebinars',
//     ]);

//     $my_vebinars = Keyboard::inlineButton([
//       'text'          => 'Мои покупки',
//       'callback_data' => 'my_vebinars',
//     ]);

//     $reply_markup = new Keyboard();
//     $reply_markup->inline();
//     $reply_markup->row(
//       $vebinar_buy,
//       $my_vebinars
//     );

//     $response = $telegram->sendMessage([
//       'chat_id'      => $order->telegram_user_id,
//       'text'         => $text,
//       'reply_markup' => $reply_markup
//     ]);

//     return redirect()->route('admin.order.edit', $order);
  }
}
