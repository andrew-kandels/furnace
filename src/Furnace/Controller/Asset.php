<?php
/**
 * Furnace Project
 *
 * This source file is subject to the BSD license bundled with
 * this package in the LICENSE.txt file. It is also available
 * on the world-wide-web at http://www.opensource.org/licenses/bsd-license.php.
 * If you are unable to receive a copy of the license or have
 * questions concerning the terms, please send an email to
 * me@andrewkandels.com.
 *
 * @category    akandels
 * @package     furnace
 * @author      Andrew Kandels (me@andrewkandels.com)
 * @copyright   Copyright (c) 2013 Andrew P. Kandels (http://andrewkandels.com)
 * @license     http://www.opensource.org/licenses/bsd-license.php BSD License
 * @link        http://contain-project.org/furnace
 */

namespace Furnace\Controller;

use Zend\Mvc\Controller\AbstractActionController;

/**
 * Furnace asset controller.
 *
 * @category   Site
 * @package    Site
 * @subpackage Site\Controller
 */
class Asset extends AbstractActionController
{
    /**
     * Exports the CSS for the jobs module (since we don't know how the application
     * is configured to serve per-module files.
     *
     * @return  Response
     */
    public function cssAction()
    {
        $response = $this->getResponse();
        $response->setContent(file_get_contents(__DIR__ . '/../../../public/css/style.css'))
            ->setStatusCode(200);

        $response->getHeaders()
            ->addHeaderLine('Content-Type', 'text/css; charset=UTF-8')
            ->addHeaderLine('Cache-Control', 'private, max-age=0')
            ->addHeaderLine('Expires', '-1');

        return $response;
    }

    /**
     * Exports a JavaScript asset for the jobs module (since we don't know how the application
     * is configured to serve per-module files.
     *
     * @return  Response
     */
    public function jsAction()
    {
        $file = $this->params()->fromRoute('file');

        $response = $this->getResponse();
        $response->setContent(file_get_contents(__DIR__ . '/../../../public/js/' . $file))
            ->setStatusCode(200);

        $response->getHeaders()
            ->addHeaderLine('Content-Type', 'application/javascript; charset=UTF-8')
            ->addHeaderLine('Cache-Control', 'private, max-age=0')
            ->addHeaderLine('Expires', '-1');

        return $response;
    }
}
