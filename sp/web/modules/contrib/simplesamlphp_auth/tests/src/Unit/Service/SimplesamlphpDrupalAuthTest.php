<?php

namespace Drupal\Tests\simplesamlphp_auth\Unit\Service;

use Drupal\Tests\UnitTestCase;
use Drupal\user\UserInterface;
use Drupal\simplesamlphp_auth\Service\SimplesamlphpDrupalAuth;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Messenger\MessengerInterface;

/**
 * SimplesamlphpDrupalAuth unit tests.
 *
 * @ingroup simplesamlphp_auth
 *
 * @group simplesamlphp_auth
 *
 * @coversDefaultClass \Drupal\simplesamlphp_auth\Service\SimplesamlphpDrupalAuth
 */
class SimplesamlphpDrupalAuthTest extends UnitTestCase {
  /**
   * The mocked SimpleSAMLphp Authentication helper.
   *
   * @var \Drupal\simplesamlphp_auth\Service\SimplesamlphpAuthManager|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $simplesaml;

  /**
   * The mocked entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityTypeManager;

  /**
   * The mocked logger instance.
   *
   * @var \Psr\Log\LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $logger;

  /**
   * The mocked config factory instance.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $configFactory;

  /**
   * The External Authentication service.
   *
   * @var \Drupal\externalauth\ExternalAuth
   */
  protected $externalauth;

  /**
   * A Mock User object to test against.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $entityAccount;

  /**
   * A mocked messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $messenger;

  /**
   * A mocked ModuleHandlerInterface.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->entityTypeManager = $this->createMock('\Drupal\Core\Entity\EntityTypeManagerInterface');

    $this->logger = $this->getMockBuilder('\Psr\Log\LoggerInterface')
      ->disableOriginalConstructor()
      ->getMock();

    $this->messenger = $this->getMockBuilder(MessengerInterface::class)
      ->disableOriginalConstructor()
      ->getMock();

    $this->moduleHandler = $this->getMockBuilder(ModuleHandlerInterface::class)
      ->disableOriginalConstructor()
      ->getMock();

    $this->moduleHandler->expects($this->any())
      ->method('alter');

    $this->simplesaml = $this->getMockBuilder('\Drupal\simplesamlphp_auth\Service\SimplesamlphpAuthManager')
      ->disableOriginalConstructor()
      ->getMock();

    $this->configFactory = $this->getConfigFactoryStub([
      'simplesamlphp_auth.settings' => [
        'register_users' => TRUE,
        'activate' => TRUE,
        'mail_attr' => 'mail',
      ],
    ]);

    $this->externalauth = $this->createMock('\Drupal\externalauth\ExternalAuthInterface');

    // Create a Mock User object to test against.
    $this->entityAccount = $this->createMock('Drupal\user\UserInterface');

  }

  /**
   * Test external load functionality.
   *
   * @covers ::externalLoginRegister
   * @covers ::__construct
   */
  public function testExternalLoginRegister() {
    $this->externalauth->expects($this->once())
      ->method('login')
      ->will($this->returnValue(FALSE));

    // Set up a mock for SimplesamlphpDrupalAuth class,
    // mocking externalRegister() method.
    $simplesaml_drupalauth = $this->getMockBuilder('Drupal\simplesamlphp_auth\Service\SimplesamlphpDrupalAuth')
      ->setMethods(['externalRegister'])
      ->setConstructorArgs([
        $this->simplesaml,
        $this->configFactory,
        $this->entityTypeManager,
        $this->logger,
        $this->externalauth,
        $this->entityAccount,
        $this->messenger,
        $this->moduleHandler,
      ])
      ->getMock();

    // Mock some methods on SimplesamlphpDrupalAuth, since they are out of scope
    // of this specific unit test.
    $simplesaml_drupalauth->expects($this->once())
      ->method('externalRegister')
      ->will($this->returnValue($this->entityAccount));

    // Now that everything is set up, call externalLoad() and expect a User.
    $loaded_account = $simplesaml_drupalauth->externalLoginRegister("testuser");
    $this->assertTrue($loaded_account instanceof UserInterface);
  }

  /**
   * Tests external login with role matching.
   *
   * @covers ::externalLoginRegister
   * @covers ::roleMatchSync
   * @covers ::evalRoleRule
   * @covers ::__construct
   */
  public function testExternalLoginWithRoleMatch() {
    // Set up specific configuration to test external login & role matching.
    $config_factory = $this->getConfigFactoryStub([
      'simplesamlphp_auth.settings' => [
        'register_users' => TRUE,
        'activate' => 1,
        'role.eval_every_time' => 1,
        'role.population' => 'student:eduPersonAffiliation,=,student',
      ],
    ]);

    // Get a Mock User object to test the external login method.
    // Expect the role "student" to be added to the user entity.
    // Expect the role "teacher" to be removed from user entity.
    $this->entityAccount->expects($this->once())
      ->method('getRoles')
      ->will($this->returnValue(['teacher']));
    $this->entityAccount->expects($this->once())
      ->method('addRole')
      ->with($this->equalTo('student'));
    $this->entityAccount->expects($this->once())
      ->method('removeRole')
      ->with($this->equalTo('teacher'));
    $this->entityAccount->expects($this->once())
      ->method('save');

    $this->externalauth->expects($this->once())
      ->method('login')
      ->will($this->returnValue($this->entityAccount));

    // Create a Mock SimplesamlphpAuthManager object.
    $simplesaml = $this->getMockBuilder('\Drupal\simplesamlphp_auth\Service\SimplesamlphpAuthManager')
      ->disableOriginalConstructor()
      ->setMethods(['getAttributes'])
      ->getMock();

    // Mock the getAttributes() method on SimplesamlphpAuthManager.
    $attributes = ['eduPersonAffiliation' => ['student']];
    $simplesaml->expects($this->any())
      ->method('getAttributes')
      ->will($this->returnValue($attributes));

    // Set up a mock for SimplesamlphpDrupalAuth class,
    // mocking getUserIdforAuthname() and externalRegister() methods.
    $simplesaml_drupalauth = $this->getMockBuilder('Drupal\simplesamlphp_auth\Service\SimplesamlphpDrupalAuth')
      ->setMethods(['externalLoginFinalize'])
      ->setConstructorArgs([
        $simplesaml,
        $config_factory,
        $this->entityTypeManager,
        $this->logger,
        $this->externalauth,
        $this->entityAccount,
        $this->messenger,
        $this->moduleHandler,
      ])
      ->getMock();

    // Now that everything is set up, call externalLogin() and expect a User.
    $simplesaml_drupalauth->externalLoginRegister("testuser");
  }

  /**
   * Test external registration functionality.
   *
   * @covers ::externalRegister
   * @covers ::__construct
   */
  public function testExternalRegister() {
    // Mock the User storage layer.
    $entity_storage = $this->createMock('Drupal\Core\Entity\EntityStorageInterface');
    // Expect the entity storage to return no existing user.
    $entity_storage->expects($this->any())
      ->method('loadByProperties')
      ->will($this->returnValue([]));

    $this->entityTypeManager->expects($this->any())
      ->method('getStorage')
      ->will($this->returnValue($entity_storage));

    // Create a Mock ExternalAuth object.
    $externalauth = $this->createMock('Drupal\externalauth\ExternalAuthInterface');

    // Set up expectations for ExternalAuth service.
    $externalauth->expects($this->once())
      ->method('register')
      ->will($this->returnValue($this->entityAccount));

    $externalauth->expects($this->once())
      ->method('userLoginFinalize')
      ->will($this->returnValue($this->entityAccount));

    // Set up a mock for SimplesamlphpDrupalAuth class,
    // mocking synchronizeUserAttributes() method.
    $simplesaml_drupalauth = $this->getMockBuilder('Drupal\simplesamlphp_auth\Service\SimplesamlphpDrupalAuth')
      ->setMethods(['synchronizeUserAttributes'])
      ->setConstructorArgs([
        $this->simplesaml,
        $this->configFactory,
        $this->entityTypeManager,
        $this->logger,
        $externalauth,
        $this->entityAccount,
        $this->messenger,
        $this->moduleHandler,
      ])
      ->getMock();

    // Mock some methods on SimplesamlphpDrupalAuth, since they are out of scope
    // of this specific unit test.
    $simplesaml_drupalauth->expects($this->once())
      ->method('synchronizeUserAttributes');

    // Now that everything is set up, call externalRegister() and expect a User.
    $registered_account = $simplesaml_drupalauth->externalRegister("testuser");
    $this->assertTrue($registered_account instanceof UserInterface);
  }

  /**
   * Tests external register with autoenablesaml setting.
   *
   * @covers ::externalRegister
   * @covers ::__construct
   */
  public function testExternalRegisterWithAutoEnableSaml() {
    $config_factory = $this->getConfigFactoryStub([
      'simplesamlphp_auth.settings' => [
        'register_users' => TRUE,
        'activate' => TRUE,
        'autoenablesaml' => TRUE,
      ],
    ]);

    // Mock the User storage layer.
    $entity_storage = $this->createMock('Drupal\Core\Entity\EntityStorageInterface');
    // Expect the entity storage to return an existing user.
    $entity_storage->expects($this->any())
      ->method('loadByProperties')
      ->will($this->returnValue([$this->entityAccount]));

    $this->entityTypeManager->expects($this->any())
      ->method('getStorage')
      ->will($this->returnValue($entity_storage));

    // Create a Mock ExternalAuth object.
    $externalauth = $this->getMockBuilder('\Drupal\externalauth\ExternalAuth')
      ->disableOriginalConstructor()
      ->setMethods(['register', 'linkExistingAccount', 'userLoginFinalize'])
      ->getMock();

    // Set up expectations for ExternalAuth service.
    $externalauth->expects($this->once())
      ->method('linkExistingAccount');

    $externalauth->expects($this->once())
      ->method('userLoginFinalize')
      ->will($this->returnValue($this->entityAccount));

    // Set up expectations for ExternalAuth service.
    $externalauth->expects($this->never())
      ->method('register');

    // Set up a mock for SimplesamlphpDrupalAuth class,
    // mocking synchronizeUserAttributes() method.
    $simplesaml_drupalauth = $this->getMockBuilder('Drupal\simplesamlphp_auth\Service\SimplesamlphpDrupalAuth')
      ->setMethods(['synchronizeUserAttributes'])
      ->setConstructorArgs([
        $this->simplesaml,
        $config_factory,
        $this->entityTypeManager,
        $this->logger,
        $externalauth,
        $this->entityAccount,
        $this->messenger,
        $this->moduleHandler,
      ])
      ->getMock();

    // Mock some methods on SimplesamlphpDrupalAuth, since they are out of scope
    // of this specific unit test.
    $simplesaml_drupalauth->expects($this->once())
      ->method('synchronizeUserAttributes');

    $simplesaml_drupalauth->externalRegister("test_authname");
  }

  /**
   * Test user attribute syncing.
   *
   * @covers ::synchronizeUserAttributes
   */
  public function testSynchronizeUserAttributes() {
    // Create a Mock SimplesamlphpAuthManager object.
    $simplesaml = $this->getMockBuilder('\Drupal\simplesamlphp_auth\Service\SimplesamlphpAuthManager')
      ->disableOriginalConstructor()
      ->setMethods(['getDefaultName', 'getDefaultEmail'])
      ->getMock();

    // Mock the getDefaultName() & getDefaultEmail methods.
    $simplesaml->expects($this->once())
      ->method('getDefaultName')
      ->will($this->returnValue("Test name"));
    $simplesaml->expects($this->once())
      ->method('getDefaultEmail')
      ->will($this->returnValue("test@example.com"));

    // Mock the User storage layer.
    $entity_storage = $this->createMock('Drupal\Core\Entity\EntityStorageInterface');
    // Expect the entity storage to return no existing user.
    $entity_storage->expects($this->any())
      ->method('loadByProperties')
      ->will($this->returnValue([]));

    $this->entityTypeManager->expects($this->any())
      ->method('getStorage')
      ->will($this->returnValue($entity_storage));

    // Get a Mock User object to test the user attribute syncing.
    $this->entityAccount->expects($this->once())
      ->method('setUsername')
      ->with($this->equalTo("Test name"));
    $this->entityAccount->expects($this->once())
      ->method('setEmail')
      ->with($this->equalTo("test@example.com"));
    $this->entityAccount->expects($this->once())
      ->method('save');

    $simplesaml_drupalauth = new SimplesamlphpDrupalAuth(
      $simplesaml,
      $this->configFactory,
      $this->entityTypeManager,
      $this->logger,
      $this->externalauth,
      $this->entityAccount,
      $this->messenger,
      $this->moduleHandler
    );

    $simplesaml_drupalauth->synchronizeUserAttributes($this->entityAccount, TRUE);
  }

  /**
   * Test role matching logic.
   *
   * @covers ::getMatchingRoles
   * @covers ::evalRoleRule
   *
   * @dataProvider roleMatchingDataProvider
   */
  public function testRoleMatching($rolemap, $attributes, $expected_roles) {
    // Set up specific configuration to test role matching.
    $config_factory = $this->getConfigFactoryStub([
      'simplesamlphp_auth.settings' => [
        'register_users' => TRUE,
        'activate' => 1,
        'role.population' => $rolemap,
      ],
    ]);

    // Create a Mock SimplesamlphpAuthManager object.
    $simplesaml = $this->getMockBuilder('\Drupal\simplesamlphp_auth\Service\SimplesamlphpAuthManager')
      ->disableOriginalConstructor()
      ->setMethods(['getAttributes'])
      ->getMock();

    // Mock the getAttributes() method on SimplesamlphpAuthManager.
    $simplesaml->expects($this->any())
      ->method('getAttributes')
      ->will($this->returnValue($attributes));

    $simplesaml_drupalauth = new SimplesamlphpDrupalAuth(
      $simplesaml,
      $config_factory,
      $this->entityTypeManager,
      $this->logger,
      $this->externalauth,
      $this->entityAccount,
      $this->messenger,
      $this->moduleHandler
    );

    $matching_roles = $simplesaml_drupalauth->getMatchingRoles();
    $this->assertEquals(count($expected_roles), count($matching_roles), 'Number of expected roles matches');
    $this->assertEquals($expected_roles, $matching_roles, 'Expected roles match');
  }

  /**
   * Provides test parameters for testRoleMatching.
   *
   * @return array
   *   Parameters
   *
   * @see \Drupal\Tests\simplesamlphp_auth\Unit\Service\SimplesamlphpDrupalAuthTest::testRoleMatching
   */
  public function roleMatchingDataProvider() {
    return [
      // Test matching of exact attribute value.
      [
        'admin:userName,=,externalAdmin|test:something,=,something',
        ['userName' => ['externalAdmin']],
        ['admin' => 'admin'],
      ],
      // Test matching of attribute portion.
      [
        'employee:mail,@=,company.com',
        ['mail' => ['joe@company.com']],
        ['employee' => 'employee'],
      ],
      // Test non-matching of attribute portion.
      [
        'employee:mail,@=,company.com',
        ['mail' => ['joe@anothercompany.com']],
        [],
      ],
      // Test matching of any attribute portion.
      [
        'employee:affiliate,~=,xyz',
        ['affiliate' => ['abcd', 'wxyz']],
        ['employee' => 'employee'],
      ],
      // Test multiple roles.
      [
        'admin:userName,=,externalAdmin|employee:mail,@=,company.com',
        ['userName' => ['externalAdmin'], 'mail' => ['externalAdmin@company.com']],
        ['admin' => 'admin', 'employee' => 'employee'],
      ],
      // Test special characters (colon) in attribute.
      [
        'admin:domain,=,http://admindomain.com',
        ['domain' => ['http://admindomain.com', 'http://drupal.org']],
        ['admin' => 'admin'],
      ],
    ];
  }

}
