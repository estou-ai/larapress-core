<?php

namespace Larapress\Mail;

/**
 * wp_mail with HTML templates and {placeholder} substitution.
 *
 * Placeholder values are escaped on the way in, since templates are rendered
 * as HTML e-mail and the values usually come from a form.
 */
class Mailer
{
    private string $fromName = '';
    private string $fromEmail = '';

    public static function make(): self
    {
        return new self();
    }

    public function from(string $name, string $email): self
    {
        $this->fromName = $name;
        $this->fromEmail = $email;

        return $this;
    }

    /**
     * @param array<string,string> $placeholders Keys without braces: ['nome' => 'Ana']
     */
    public function send(string $to, string $subject, string $body, array $placeholders = []): bool
    {
        $replacements = [];

        foreach ($placeholders as $key => $value) {
            $replacements['{' . $key . '}'] = esc_html((string) $value);
        }

        // $to and the display name may both originate from a front-end form,
        // so neither reaches the headers raw: an address that stops being
        // valid after sanitising is refused, and CR/LF in the name would
        // otherwise inject extra headers into wp_mail.
        $to = sanitize_email($to);

        if (!is_email($to)) {
            error_log(sprintf('[larapress] e-mail rejected: invalid recipient for "%s"', $subject));
            return false;
        }

        $headers = ['Content-Type: text/html; charset=UTF-8'];

        if (is_email($this->fromEmail)) {
            $name = trim((string) preg_replace('/[\r\n]+/', ' ', $this->fromName));

            $headers[] = sprintf('From: %s <%s>', $name, $this->fromEmail);
        }

        $sent = wp_mail(
            $to,
            wp_specialchars_decode(strtr($subject, $replacements)),
            strtr($body, $replacements),
            $headers
        );

        if (!$sent) {
            // Masked recipient: debug logs are often readable more widely
            // than the address book they would otherwise accumulate.
            error_log(sprintf(
                '[larapress] e-mail to %s failed: %s',
                preg_replace('/^[^@]+@/', '***@', $to),
                $subject
            ));
        }

        return $sent;
    }
}
