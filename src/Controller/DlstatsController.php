<?php

declare(strict_types=1);

/*
 * This file is part of a BugBuster Contao Bundle.
 *
 * @copyright  Glen Langer 2024 <http://contao.ninja>
 * @author     Glen Langer (BugBuster)
 * @package    Contao Download Statistics Bundle (Dlstats)
 * @link       https://github.com/BugBuster1701/contao-dlstats-bundle
 *
 * @license    LGPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 */

namespace BugBuster\DlstatsBundle\Controller;

use BugBuster\DLStats\BackendDlstats;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Handles the dlstats back end routes.
 */
#[Route('/dlstats', defaults: ['_scope' => 'backend', '_token_check' => true])]
class DlstatsController extends AbstractController
{
    /**
     * Renders the alerts content.
     *
     * @return Response
     */
    #[Route('/details', name: 'dlstats_backend_details')]
    public function detailsAction()
    {
        $this->container->get('contao.framework')->initialize();

        $controller = new BackendDlstats();

        return $controller->run();
    }
}
