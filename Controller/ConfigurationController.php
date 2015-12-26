<?php

namespace ThirdEngine\PropelSOABundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;


class ConfigurationController extends Controller
{
    /**
     * This action allows us to generate configuration information used by propelsoa javascript
     * here on the server side where we have access to more information.
     */
    public function indexAction()
    {
        $viewData = array();
        return $this->render('PropelSOABundle:Configuration:index.js.twig', $viewData);
    }
}
