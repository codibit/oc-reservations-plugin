<?php namespace VojtaSvoboda\Reservations\Mailers;

use App;
use Config;
use Mail;
use Request;
use VojtaSvoboda\Reservations\Facades\ReservationsFacade;
use VojtaSvoboda\Reservations\Models\Reservation;

class ReservationMailer
{
    /** Default template locale. */
    const DEFAULT_TEMPLATE_LOCALE = 'en';

    /**
     * Send reservation confirmation mail.
     *
     * @param Reservation $reservation
     */
    public static function send(Reservation $reservation)
    {
        if (App::environment() !== 'production') {
            return;
        }

        $locale = App::getLocale();
        $appUrl = Request::url();
        $recipients['email'] = $reservation->email;
        $recipients['name'] = trim($reservation->name . ' ' . $reservation->lastname);
        $recipients['bcc_email'] = Config::get('vojtasvoboda.reservations::config.mail.bcc_email');
        $recipients['bcc_name'] = Config::get('vojtasvoboda.reservations::config.mail.bcc_name');

        $template = self::getTemplateIdent();

        $templateParameters = [
            'site' => $appUrl,
            'reservation' => $reservation,
            'locale' => $locale,
            'reservationsCount' => self::getReservationsCount($reservation->email),
        ];

        Mail::send($template, $templateParameters, function($message) use ($recipients)
        {
            $message->to($recipients['email'], $recipients['name']);

            if (!empty($recipients['bcc_email']) && !empty($recipients['bcc_name'])) {
                $message->bcc($recipients['bcc_email'], $recipients['bcc_name']);
            }
        });
    }

    /**
     * Get template ident by locale.
     *
     * @return string
     */
    public static function getTemplateIdent()
    {
        $locale = App::getLocale();
        $identBase = 'vojtasvoboda.reservations::mail.reservation-';
        $ident = $identBase . $locale;

        if (file_exists(__DIR__ . '/../views/mail/' . $ident . '.htm')) {
            return $ident;
        }

        return $identBase . self::DEFAULT_TEMPLATE_LOCALE;
    }

    /**
     * Get reservations count.
     *
     * @param $email
     *
     * @return int
     */
    public static function getReservationsCount($email)
    {
        /** @var ReservationsFacade $facade */
        $facade = App::make('vojtasvoboda.reservations.facade');

        return $facade->getReservationsWithSameEmailCount($email);
    }
}
