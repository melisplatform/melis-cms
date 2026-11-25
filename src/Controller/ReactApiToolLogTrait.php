<?php

namespace MelisCms\Controller;

/**
 * Logging of the React site tools (Mantis #0011561).
 *
 * The legacy controllers end each create / update / delete with a `*_end` event carrying
 * success, textTitle, textMessage, typeCode and itemId; MelisCmsFlashMessengerListener turns it
 * into the bell notification and the MelisCore > Logs entry. The React API controllers saved
 * silently, so nothing reached the logs. They now fire the same event with the same type code
 * and legacy translation keys, after the change succeeded.
 */
trait ReactApiToolLogTrait
{
    private function logToolAction(string $event, string $typeCode, int $itemId, string $title, string $message): void
    {
        try {
            $this->getEventManager()->trigger($event, $this, [
                'success'     => 1,
                'textTitle'   => $title,
                'textMessage' => $message,
                'errors'      => [],
                'typeCode'    => $typeCode,
                'itemId'      => $itemId,
            ]);
        } catch (\Throwable $e) {
            // The change is already saved: a logging failure must not turn it into an error.
            error_log('MelisCms React tool log (' . $typeCode . '): ' . $e->getMessage());
        }
    }
}
