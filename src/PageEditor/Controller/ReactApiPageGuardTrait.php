<?php

namespace MelisCms\PageEditor\Controller;

use Laminas\Http\PhpEnvironment\Response as HttpResponse;

/**
 * Shared guard + JSON helpers for the new React page-editor react-api controllers
 * (branch evo/page-edition-react). Same access model as the rest of the page
 * react-api: authenticated + `canAccess(meliscms_page)`. The using class must
 * define `const MELIS_KEY`.
 */
trait ReactApiPageGuardTrait
{
    private function isAuthenticated(): bool
    {
        return $this->getServiceManager()->get('MelisCoreAuth')->hasIdentity();
    }

    private function denyUnlessAccess(): ?HttpResponse
    {
        if (!$this->isAuthenticated()) {
            return $this->jsonResponse(['success' => false, 'error' => 'Unauthenticated'], 401);
        }
        try {
            if (!$this->getServiceManager()->get('MelisCoreRights')->canAccess(self::MELIS_KEY)) {
                return $this->jsonResponse(['success' => false, 'error' => 'Forbidden'], 403);
            }
        } catch (\Throwable) {
        }
        return null;
    }

    /**
     * Droit par page (section `meliscms_pages`) en plus du droit de l'outil, via canAccessPage.
     * À appeler APRÈS denyUnlessAccess, avec l'idPage déjà casté.
     */
    private function denyUnlessPageRight(int $idPage): ?HttpResponse
    {
        if (!$this->getServiceManager()->get('MelisCmsRights')->canAccessPage($idPage)) {
            return $this->jsonResponse(['success' => false, 'error' => 'Forbidden (page rights)'], 403);
        }
        return null;
    }

    /**
     * Capacité d'action (boutons save/publish/unpublish/delete des interface rights,
     * `MelisCmsRights::isActionButtonActive`) vérifiée côté serveur.
     */
    private function denyUnlessPageAction(string $action): ?HttpResponse
    {
        try {
            if ($this->getServiceManager()->get('MelisCmsRights')->isActionButtonActive($action)) {
                return null;
            }
        } catch (\Throwable) {
            // échec de résolution des droits → refus, jamais l'inverse
        }
        return $this->jsonResponse(['success' => false, 'error' => 'Forbidden (action rights)'], 403);
    }

    private function jsonResponse(array $data, int $status = 200): HttpResponse
    {
        /** @var HttpResponse $response */
        $response = $this->getResponse();
        $response->setStatusCode($status);
        $response->getHeaders()->addHeaders([
            'Content-Type'           => 'application/json; charset=utf-8',
            'X-Content-Type-Options' => 'nosniff',
        ]);
        $response->setContent(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        return $response;
    }
}
