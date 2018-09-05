<?php

/**
 * @copyright  Glen Langer 2018 <http://contao.ninja>
 * @author     Glen Langer (BugBuster)
 * @package    Visitors
 * @license    LGPL-3.0+
 * @see	       https://github.com/BugBuster1701/contao-visitors-bundle
 */

namespace BugBuster\DlstatsBundle\Controller;

use BugBuster\DLStats\BackendDlstats;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Handles the dlstats back end routes.
 *
 * @copyright  Glen Langer 2018 <http://contao.ninja>
 * @author     Glen Langer (BugBuster)
 *
 * @Route("/dlstats", defaults={"_scope" = "backend", "_token_check" = true})
 */
class DlstatsController extends Controller
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
