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

        $headers = ['Content-Type: text/html; charset=UTF-8'];

        if (is_email($this->fromEmail)) {
            $headers[] = sprintf('From: %s <%s>', $this->fromName, $this->fromEmail);
        }

        $sent = wp_mail(
            $to,
            wp_specialchars_decode(strtr($subject, $replacements)),
            strtr($body, $replacements),
            $headers
        );

        if (!$sent) {
            error_log(sprintf('[larapress] e-mail to %s failed: %s', $to, $subject));
        }

        return $sent;
    }
}
