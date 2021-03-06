<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magebird\Popup\Controller\Index;

use Magento\Customer\Model\Account\Redirect as AccountRedirect;
use Magento\Framework\App\Action\Context;
use Magento\Customer\Model\Session;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Model\Url as CustomerUrl;
use Magento\Framework\Exception\EmailNotConfirmedException;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Exception\State\UserLockedException;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Login extends \Magento\Customer\Controller\AbstractAccount
{
    /** @var AccountManagementInterface */
    protected $customerAccountManagement;

    /** @var Validator */
    protected $formKeyValidator;

    /**
     * @var AccountRedirect
     */
    protected $accountRedirect;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory
     */
    private $cookieMetadataFactory;

    /**
     * @var \Magento\Framework\Stdlib\Cookie\PhpCookieManager
     */
    private $cookieMetadataManager;

    /**
     * @param Context $context
     * @param Session $customerSession
     * @param AccountManagementInterface $customerAccountManagement
     * @param CustomerUrl $customerHelperData
     * @param Validator $formKeyValidator
     * @param AccountRedirect $accountRedirect
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        AccountManagementInterface $customerAccountManagement,
        CustomerUrl $customerHelperData,
        Validator $formKeyValidator,
        AccountRedirect $accountRedirect
    ) {
        $this->session = $customerSession;
        $this->customerAccountManagement = $customerAccountManagement;
        $this->customerUrl = $customerHelperData;
        $this->formKeyValidator = $formKeyValidator;
        $this->accountRedirect = $accountRedirect;
        parent::__construct($context);
    }

    /**
     * Get scope config
     *
     * @return ScopeConfigInterface
     * @deprecated
     */
    private function getScopeConfig()
    {
        if (!($this->scopeConfig instanceof \Magento\Framework\App\Config\ScopeConfigInterface)) {
            return \Magento\Framework\App\ObjectManager::getInstance()->get(
                \Magento\Framework\App\Config\ScopeConfigInterface::class
            );
        } else {
            return $this->scopeConfig;
        }
    }

    /**
     * Retrieve cookie manager
     *
     * @deprecated
     * @return \Magento\Framework\Stdlib\Cookie\PhpCookieManager
     */
    private function getCookieManager()
    {
        if (!$this->cookieMetadataManager) {
            $this->cookieMetadataManager = \Magento\Framework\App\ObjectManager::getInstance()->get(
                \Magento\Framework\Stdlib\Cookie\PhpCookieManager::class
            );
        }
        return $this->cookieMetadataManager;
    }

    /**
     * Retrieve cookie metadata factory
     *
     * @deprecated
     * @return \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory
     */
    private function getCookieMetadataFactory()
    {
        if (!$this->cookieMetadataFactory) {
            $this->cookieMetadataFactory = \Magento\Framework\App\ObjectManager::getInstance()->get(
                \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory::class
            );
        }
        return $this->cookieMetadataFactory;
    }

    /**
     * Login post action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute()
    {
        
        if ($this->session->isLoggedIn()) {
            $ajaxExceptions['exceptions'][] = __("You are already logged in.");
            $response = json_encode($ajaxExceptions);
            $this->getResponse()->setBody($response); 
            return;   
        }
        

   
            $login = $this->getRequest()->getParams();
            if (!empty($login['login_email']) && !empty($login['login_password'])) {
                try {
                    $customer = $this->customerAccountManagement->authenticate($login['login_email'], $login['login_password']);
                    $this->session->setCustomerDataAsLoggedIn($customer);
                    $this->session->regenerateId();
                    if ($this->getCookieManager()->getCookie('mage-cache-sessid')) {
                        $metadata = $this->getCookieMetadataFactory()->createCookieMetadata();
                        $metadata->setPath('/');
                        $this->getCookieManager()->deleteCookie('mage-cache-sessid', $metadata);
                    }
                    //$redirectUrl = $this->accountRedirect->getRedirectCookie();
                    $response = json_encode(array('success' => 'success'));
                    $this->getResponse()->setBody($response); 
                    return; 
                } catch (EmailNotConfirmedException $e) {
                    $ajaxExceptions['exceptions'][] = $message;
                    $response = json_encode($ajaxExceptions);
                    $this->getResponse()->setBody($response); 
                    return;                      
                } catch (UserLockedException $e) {
                    $message = __(
                        'The account is locked. Please wait and try again or contact %1.',
                        $this->getScopeConfig()->getValue('contact/email/recipient_email')
                    );
                    $ajaxExceptions['exceptions'][] = $message;
                    $response = json_encode($ajaxExceptions);
                    $this->getResponse()->setBody($response); 
                    return;  
                } catch (AuthenticationException $e) {
                    $message = __('Invalid login or password.');
                    $ajaxExceptions['exceptions'][] = $message;
                    $response = json_encode($ajaxExceptions);
                    $this->getResponse()->setBody($response); 
                    return;  
                } catch (\Exception $e) {
                    // PA DSS violation: throwing or logging an exception here can disclose customer password
                    $ajaxExceptions['exceptions'][] = __('An unspecified error occurred. Please contact us for assistance.');
                    $response = json_encode($ajaxExceptions);
                    $this->getResponse()->setBody($response); 
                    return;                      
                }
            } else {
                    $ajaxExceptions['exceptions'][] = __('A login and a password are required.');
                    $response = json_encode($ajaxExceptions);
                    $this->getResponse()->setBody($response); 
                    return;                   
            }
       

        
    }
}
