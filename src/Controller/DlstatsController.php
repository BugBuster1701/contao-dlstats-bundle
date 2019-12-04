<?php

declare(strict_types=1);

/*
 * This file is part of a BugBuster Contao Bundle
 *
 * @copyright  Glen Langer 2008..2019 <http://contao.ninja>
 * @author     Glen Langer (BugBuster)
 * @package    Contao Download Statistics Bundle (Dlstats)
 * @license    LGPL-3.0-or-later
 * @see        https://github.com/BugBuster1701/contao-dlstats-bundle
 */

namespace BugBuster\DlstatsBundle\Controller;

use BugBuster\DLStats\BackendDlstats;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Handles the dlstats back end routes.
 *
 * @copyright  Glen Langer 2018 <http://contao.ninja>
 *
 * @Route("/dlstats", defaults={"_scope" = "backend", "_token_check" = true})
 */
class DlstatsController extends AbstractController
{
    /**
     * Renders the alerts content.
     *
     * @return Response
     *
     * @Route("/details", name="dlstats_backend_details")
     */
    public function detailsAction()
    {
        $this->container->get('contao.framework')->initialize();

        $controller = new BackendDlstats();

        return $controller->run();
    }
}
