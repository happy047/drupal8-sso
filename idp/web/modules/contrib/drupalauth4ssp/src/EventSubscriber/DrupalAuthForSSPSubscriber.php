<?php

namespace Drupal\drupalauth4ssp\EventSubscriber;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * DrupalAuth for SimpleSAMLphp event subscriber.
 */
class DrupalAuthForSSPSubscriber implements EventSubscriberInterface {

  /**
   * Account proxy.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $accountProxy;

  /**
   * Constructs event subscriber.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $account_proxy
   *   Account proxy.
   */
  public function __construct(AccountProxyInterface $account_proxy) {
    $this->accountProxy = $account_proxy;
  }

  /**
   * Kernel response event handler.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   Response event.
   */
  public function checkRedirection(FilterResponseEvent $event) {

    if ($event->getResponse() instanceof RedirectResponse) {
      $response = $event->getResponse();
      $path = $response->getTargetUrl();
      $frontPage = Url::fromRoute('<front>')->setAbsolute()->toString();

      // Redirect after log out.
      $responseIsHttpFound = $response->getStatusCode() === Response::HTTP_FOUND;
      $isRedirectToFrontPage = ($path === $frontPage && $responseIsHttpFound);
      $destination = &drupal_static('drupalauth4ssp_user_logout');
      if ($isRedirectToFrontPage && !empty($destination)) {
        $response->setTargetUrl($destination);
        $event->stopPropagation();
        return;
      }

      // If this was request to login and user was authenticated by cookie.
      $returnTo = $event->getRequest()->query->get('ReturnTo');
      $isLoginRequest = $event->getRequest()->attributes->get('_route') === 'user.login';
      if ($isLoginRequest && $responseIsHttpFound && $returnTo) {
        drupalauth4ssp_set_user_cookie($this->accountProxy);
        $response->setTargetUrl($returnTo);
        $event->stopPropagation();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      KernelEvents::RESPONSE => ['checkRedirection'],
    ];
  }

}
