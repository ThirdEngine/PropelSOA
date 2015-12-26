<?php
/**
 * This class defines a controller interface for setting and getting default limiters to be
 * used on any API queries (just db queries, not other api calls).
 */

namespace SOA\SOABundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Session\Session;


class DefaultCriteriaController extends Controller
{
    /**
     * This actions will just route to the accompanying method.
     * 
     * @param $fieldName
     * @param $value
     */
    public function setDefaultCriteriaAction($fieldName, $value)
    {
        $this->setDefaultCriteria($fieldName, $value);
        exit;
    }


    /**
     * This actions will just route to the accompanying method.
     * 
     * @param $fieldName
     */
    public function getDefaultCriteriaAction($fieldName)
    {
        return $this->getDefaultCriteria($fiedlName);
    }


    /**
     * This method will set a new default limiter on every query if the specified field is in the
     * main table being queried.
     * 
     * @param $fieldName
     * @param $value
     */ 
    public function setDefaultCriteria($fieldName, $value)
    {
        $session = $this->getRequest()->getSession();

        $defaultCriteria = array();
        if ($session->has('SOA_defaultCriteria')) 
        {
            $currentValue = $session->get('SOA_defaultCriteria');

            if (is_array($currentValue))
            {
                $defaultCriteria = $currentValue;
            }
        }


        // next, we need to check to see if this key has already been defined. if so, make
        // sure we just replace the value

        $foundKey = false;

        foreach ($defaultCriteria as $key => $criteriaInfo)
        {
            if ($criteriaInfo['fieldName'] == $fieldName)
            {
                $defaultCriteria[$key]['value'] = $value;
                $foundKey = true;
            }
        }

        if (!$foundKey)
        {
            $defaultCriteria[] = array(
                'fieldName' => $fieldName,
                'value'     => $value
            );    
        }

        $session->set('SOA_defaultCriteria', $defaultCriteria);
    }


    /**
     * This method will return any default criteria that should be added to all db queries.
     * 
     * @return array
     */
    public function getDefaultCriteria()
    {
        $session = $this->getRequest()->getSession();
        $defaultCriteria = array();

        if ($session->has('SOA_defaultCriteria'))
        {
            $defaultCriteria = $session->get('SOA_defaultCriteria');
        }

        return json_encode($defaultCriteria);
    }
}