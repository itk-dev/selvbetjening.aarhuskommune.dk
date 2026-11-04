<?php

namespace Drupal\Tests\config_entity_revisions\Unit;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\config_entity_revisions\ConfigEntityRevisionsEntityInterface;
use Drupal\config_entity_revisions\ConfigEntityRevisionsInterface;
use Drupal\config_entity_revisions\ConfigEntityRevisionsRevertFormBase;
use Drupal\config_entity_revisions\Entity\ConfigEntityRevisions;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\ContentEntityStorageBase;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Routing\AccessAwareRouter;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslationManager;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Test ability to revert (form) configurations.
 *
 * @group config_entity_revisions
 */
class ConfigEntityRevisionsRevertFormBaseTest extends UnitTestCase {

  use ProphecyTrait;
  /**
   * Mock container.
   *
   * @var \Drupal\Core\DependencyInjection\Container
   */
  private $container;

  /**
   * Mock old revision.
   *
   * @var \Drupal\config_entity_revisions\Entity\ConfigEntityRevisions
   */
  private $mockOldRevision;

  /**
   * Mock new revision.
   *
   * @var \Drupal\config_entity_revisions\Entity\ConfigEntityRevisions
   */
  private $mockNewRevision;

  /**
   * Mock default revision.
   *
   * @var \Drupal\config_entity_revisions\Entity\ConfigEntityRevisions
   */
  private $mockDefaultRevision;

  /**
   * Class instance.
   *
   * @var \Drupal\config_entity_revisions\ConfigEntityRevisionsInterface
   */
  private $configEntity;

  /**
   * Form instance.
   *
   * @var \Drupal\Tests\config_entity_revisions\Unit\ConfigEntityRevisionsRevertFormTest
   */
  private $instance;

  /**
   * Mock serialiser.
   *
   * @var \Drupal\Tests\config_entity_revisions\Unit\MockSerializer
   */
  private $serializer;

  /**
   * Set up for a test.
   */
  public function setup(): void {

    parent::setup();
    $entityTypeManager = $this->prophesize(EntityTypeManager::CLASS);

    $dateFormatter = $this->prophesize(DateFormatter::CLASS);
    $dateFormatter->format(Argument::any())->will(function ($calls) {
      return $calls[count($calls) - 1];
    });

    $dateTime = $this->prophesize(TimeInterface::CLASS);
    $dateTime->getRequestTime()->willReturn(1260);

    $this->mockOldRevision = $this->prophesize(ConfigEntityRevisions::CLASS);
    $this->mockOldRevision->id()->willReturn(2);
    $this->mockOldRevision->getRevisionId()->willReturn(300);
    $this->mockOldRevision->getRevisionCreationTime()->willReturn(1200);
    $this->mockOldRevision->isPublished()->willReturn(TRUE);
    $this->mockOldRevision->get(Argument::type('string'))
      ->willReturn((object) ['value' => 'serialisedConfiguration']);

    $this->mockDefaultRevision = $this->prophesize(ConfigEntityRevisions::CLASS);
    $this->mockDefaultRevision->id()->willReturn(2);
    $this->mockDefaultRevision->getRevisionId()->willReturn(321);
    $this->mockDefaultRevision->getRevisionCreationTime()->willReturn(1234);
    $this->mockDefaultRevision->isPublished()->willReturn(TRUE);

    $this->mockNewRevision = $this->prophesize(ConfigEntityRevisions::CLASS);
    $this->mockNewRevision->id()->willReturn(2);
    $this->mockNewRevision->getRevisionId()->willReturn(324);
    $this->mockNewRevision->getRevisionCreationTime()->willReturn(1245);
    $this->mockNewRevision->isPublished()->willReturn(FALSE);

    $mockContentStorage = $this->prophesize(ContentEntityStorageBase::CLASS);
    $mockContentStorage->loadRevision(300)
      ->willReturn($this->mockOldRevision->reveal());
    $mockContentStorage->loadRevision(321)
      ->willReturn($this->mockDefaultRevision->reveal());
    $mockContentStorage->loadRevision(324)
      ->willReturn($this->mockNewRevision->reveal());

    $config_entity = $this->prophesize(ConfigEntityRevisionsInterface::CLASS);
    $config_entity->contentEntityStorage()->willReturn($mockContentStorage);
    $config_entity->getContentEntity()
      ->willReturn($this->mockDefaultRevision->reveal());
    $config_entity->getEntityTypeId()->willReturn('my_entity_type');
    $config_entity->id()->willReturn('foozbar');
    $config_entity->moduleName()->willReturn('module_name');
    $config_entity->label()->willReturn('config_entity_label');
    $config_entity->title()->willReturn('config_entity_title');
    $this->configEntity = $config_entity;

    $mock_request = new Request();
    $request_stack = $this->prophesize(RequestStack::CLASS);
    $request_stack->getCurrentRequest()->willReturn($mock_request);

    $string_translation = $this->prophesize(TranslationManager::CLASS);

    $currentUser = $this->prophesize(AccountInterface::CLASS);
    $currentUser->id()->willReturn(1717);

    $this->serializer = new MockSerializer($this);

    $context = &$this;
    $logger = $this->prophesize(LoggerInterface::CLASS);
    $logger->notice(Argument::type('string'), Argument::type('array'))
      ->will(function ($args) use ($context) {
        $context->assertEquals('@form: set @form to revision %revision.', $args[0]);
        $context->assertEquals([
          '@form' => 'config_entity_label',
          '%revision' => 300,
        ], $args[1]);
      });

    $loggerFactory = $this->prophesize(LoggerChannelFactory::CLASS);
    $loggerFactory->get('content')->willReturn($logger->reveal());

    $messenger = $this->prophesize(Messenger::CLASS);
    $messenger->addMessage(Argument::type('Drupal\Core\StringTranslation\TranslatableMarkup'))->will(function ($calls) use ($context) {
      $message = $calls[0];
      $context->assertEquals('%entity_title %title has been set to the revision from %revision-date.',
        $message->getUntranslatedString());

      $context->assertEquals([
        '%entity_title' => 'config_entity_title',
        '%title' => 'config_entity_label',
        '%revision-date' => 1200,
      ], $message->getArguments());
    });

    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $entityTypeManager->reveal());
    $container->set('date.formatter', $dateFormatter->reveal());
    $container->set('datetime.time', $dateTime->reveal());
    $container->set('request_stack', $request_stack->reveal());
    $container->set('string_translation', $string_translation->reveal());
    $container->set('current_user', $currentUser->reveal());
    $container->set('serializer', $this->serializer);
    $container->set('logger.factory', $loggerFactory->reveal());
    $container->set('messenger', $messenger->reveal());
    $this->container = $container;
  }

  /**
   * Proxy prophesize() for the mock deserializer.
   *
   * (see below for why that's needed).
   */
  public function prophesizeProxy($classOrInterface = NULL) {
    return parent::prophesize($classOrInterface);
  }

  /**
   * Generate a mock request.
   *
   * @param int $revisionId
   *   The revision ID being 'reverted'.
   * @param string $classname
   *   The class to be instantiated.
   *
   * @return \Drupal\Tests\config_entity_revisions\Unit\ConfigEntityRevisionsRevertFormTest
   *   The resulting test class instance.
   */
  public function getMockInstance(int $revisionId, $classname = 'ConfigEntityRevisionsRevertFormTest') {

    $configEntity = $this->configEntity;

    $mock_request = new Request();
    $request_stack = $this->prophesize(RequestStack::CLASS);
    $request_stack->getCurrentRequest()->willReturn($mock_request);

    $router = $this->prophesize(AccessAwareRouter::CLASS);
    $router->matchRequest($mock_request)
      ->will(function () use ($configEntity, $revisionId) {
        return [
          'config_entity' => $configEntity,
          'revision_id' => $revisionId,
        ];
      });
    $this->container->set('router', $router->reveal());

    \Drupal::setContainer($this->container);

    $classname = 'Drupal\\Tests\\config_entity_revisions\\Unit\\' . $classname;

    return $classname::create($this->container);
  }

  /**
   * Check that getFormId includes the module name.
   *
   * @test
   */
  public function formIdStartsWithModuleName() {
    $this->instance = $this->getMockInstance(300);
    $this->assertEquals($this->instance->getFormId(), 'module_name_revision_revert_confirm');
  }

  /**
   * Check that confirmation question is as expected.
   *
   * @test
   */
  public function questionContainsActionAndRevisionDate() {
    $this->instance = $this->getMockInstance(300);
    $actual = $this->instance->getQuestion();
    $this->assertEquals("Are you sure you want to %action to the revision from %revision-date?", $actual->getUntranslatedString());

    $args = $actual->getArguments();
    $this->assertEquals([
      '%revision-date' => 1200,
      '%action' => 'revert',
    ], $args);
  }

  /**
   * Check that the cancel URL is as expected.
   *
   * @test
   */
  public function cancelUrlIsAsExpected() {
    $this->instance = $this->getMockInstance(300);
    $actual = $this->instance->getCancelUrl();
    $this->assertEquals('entity.my_entity_type.revisions', $actual->getRouteName());
    $this->assertEquals([
      'my_entity_type' => 'foozbar',
    ], $actual->getRouteParameters());
  }

  /**
   * Check that action is calculated as expected.
   *
   * @test
   */
  public function actionDependsOnWhetherTheRevisionIsPublished() {

    // Start with a revision older than the published / default revision.
    $this->instance = $this->getMockInstance(300);

    $actual = $this->instance->get('action');
    $this->assertEquals('revert', $actual);

    // Now use a revision after the published / default revision.
    $this->instance = $this->getMockInstance(324);

    $actual = $this->instance->get('action');
    $this->assertEquals('publish', $actual);
  }

  /**
   * Validate the built render array. Should be just what the parent provides.
   *
   * @test
   */
  public function buildFormProducesExpectedRenderArray() {

    // Start with a revision older than the published / default revision.
    $this->instance = $this->getMockInstance(300);

    $formState = $this->prophesize(FormStateInterface::CLASS);

    $actual = $this->instance->buildForm([], $formState->reveal());
    $this->arrayHasKey('#title', $actual);
    // Like above but now we're confirming the question has made it into the
    // form.
    $this->assertEquals("Are you sure you want to %action to the revision from %revision-date?", $actual['#title']->getUntranslatedString());

    $args = $actual['#title']->getArguments();
    $this->assertEquals([
      '%revision-date' => 1200,
      '%action' => 'revert',
    ], $args);

    $this->assertArrayHasKey('#attributes', $actual);
    $this->assertEquals(
      [
        'class' => [
          0 => 'confirmation',
        ],
      ], $actual['#attributes']);

    $this->assertEquals([
      '#type',
      'submit',
      'cancel',
    ], array_keys($actual['actions']));

    $this->assertArrayHasKey('#theme', $actual);
    $this->assertEquals('confirm_form', $actual['#theme']);
  }

  /**
   * Check that preparation of a reverted revision does all it should.
   *
   * @test
   */
  public function revertedVersionOfRevisionGeneratedCorrectly() {

    $this->instance = $this->getMockInstance(300);

    $revision = $this->mockOldRevision;
    $revision->setNewRevision()->shouldBeCalled();
    $revision->isDefaultRevision(Argument::type('bool'))
      ->shouldBeCalled()
      ->should(function ($calls) {
        if (!$calls || !$calls[0]->getArguments()[0]) {
          throw new \Exception("isDefaultRevision wasn't set to TRUE.");
        };
      });

    $revision->getRevisionLogMessage()->willReturn('Vanity of vanities!');

    $savedMessage = NULL;
    $revision->setRevisionLogMessage(Argument::type("Drupal\Core\StringTranslation\TranslatableMarkup"))
      ->will(function ($newMessage) use (&$savedMessage) {
        $savedMessage = $newMessage[0];
      });

    $savedUserId = NULL;
    $revision->setRevisionUserId(Argument::type('int'))
      ->will(function ($newUserId) use (&$savedUserId) {
        $savedUserId = $newUserId[0];
      });

    $savedCreationTime = NULL;
    $revision->setRevisionCreationTime(Argument::type('int'))
      ->will(function ($newCreationTime) use (&$savedCreationTime) {
        $savedCreationTime = $newCreationTime[0];
      });

    $savedChangedTime = NULL;
    $revision->setChangedTime(Argument::type('int'))
      ->will(function ($newChangedTime) use (&$savedChangedTime) {
        $savedChangedTime = $newChangedTime[0];
      });

    $revision->setUnpublished()->shouldBeCalled();

    $key_value_pairs = [];
    $revision->set(Argument::type('string'), Argument::any())
      ->will(function ($arguments) use (&$key_value_pairs) {
        $key_value_pairs[$arguments[0]] = $arguments[1];
      });

    $this->instance->prepareRevertedRevision($revision->reveal());

    $this->assertEquals('Copy of the revision from %date (%message).', $savedMessage->getUntranslatedString());
    $this->assertEquals(1717, $savedUserId);
    $this->assertEquals(1260, $savedCreationTime);
    $this->assertEquals(1260, $savedChangedTime);
    $this->assertEquals(['moderation_state' => 'draft'], $key_value_pairs);

    // And without a revision log message.
    $revision->getRevisionLogMessage()->willReturn();

    $this->instance->prepareRevertedRevision($revision->reveal());

    $this->assertEquals('Copy of the revision from %date.', $savedMessage->getUntranslatedString());
    $this->assertEquals(1717, $savedUserId);
    $this->assertEquals(1260, $savedCreationTime);
    $this->assertEquals(1260, $savedChangedTime);
    $this->assertEquals(['moderation_state' => 'draft'], $key_value_pairs);
  }

  /**
   * Check that preparation of a published revision does all it should.
   *
   * @test
   */
  public function publishedVersionOfRevisionGeneratedCorrectly() {

    $this->instance = $this->getMockInstance(324);

    $revision = $this->mockNewRevision;
    $revision->setNewRevision()->shouldNotBeCalled();
    $revision->isDefaultRevision(Argument::type('bool'))
      ->shouldBeCalled()
      ->should(function ($calls) {
        if (!$calls || !$calls[0]->getArguments()[0]) {
          throw new \Exception("isDefaultRevision wasn't set to TRUE.");
        };
      });

    $revision->setRevisionUserId(Argument::type('int'))->shouldNotBeCalled();
    $revision->setRevisionCreationTime(Argument::type('int'))
      ->shouldNotBeCalled();
    $revision->setChangedTime(Argument::type('int'))->shouldNotBeCalled();
    $revision->setPublished()->shouldBeCalled();

    $key_value_pairs = [];
    $revision->set(Argument::type('string'), Argument::any())
      ->will(function ($arguments) use (&$key_value_pairs) {
        $key_value_pairs[$arguments[0]] = $arguments[1];
      });

    $this->instance->prepareToPublishCurrentRevision($revision->reveal());

    $this->assertEquals(['moderation_state' => 'published'], $key_value_pairs);
  }

  /**
   * Check that applyRevisionChange invokes the right fn and seeks to save.
   *
   * @test
   */
  public function applyRevisionChangeCallsRightFunctionAndSaves() {

    // Older revision -> revert called.
    $this->instance = $this->getMockInstance(300, 'ConfigEntityRevisionsRevertFormTest2');

    $revision = $this->mockOldRevision;
    $revision->save()->shouldBeCalled();

    $this->instance->applyRevisionChange();
    $this->assertTrue($this->instance->get('prepareRevertedRevisionCalled'));
    $this->assertFalse($this->instance->get('prepareToPublishCurrentRevisionCalled'));

    // Newer revision -> publish called.
    $this->instance = $this->getMockInstance(324, 'ConfigEntityRevisionsRevertFormTest2');

    $revision = $this->mockNewRevision;
    $revision->save()->shouldBeCalled();

    $this->instance->applyRevisionChange();
    $this->assertFalse($this->instance->get('prepareRevertedRevisionCalled'));
    $this->assertTrue($this->instance->get('prepareToPublishCurrentRevisionCalled'));
  }

  /**
   * UpdateConfigEntity should update the config entity as expected.
   *
   * @test
   */
  public function updateConfigEntityModifiesEntityCorrectly() {
    $this->instance = $this->getMockInstance(300, 'ConfigEntityRevisionsRevertFormTest2');
    $this->instance->updateConfigEntity();

    $this->assertTrue($this->serializer->wasCalled);
    $this->assertEquals([
      'settingsOriginal' => 'originalSettings',
      'revision_id' => 300,
    ], $this->serializer->keyValuePairs);
  }

  /**
   * Does the logUpdate method include expected information?
   *
   * @test
   */
  public function logUpdate() {
    $this->instance = $this->getMockInstance(300, 'ConfigEntityRevisionsRevertFormTest2');
    $this->instance->logUpdate();
  }

  /**
   * Does displayUpdate display the expected message?
   *
   * @test
   */
  public function displayUpdate() {
    $this->instance = $this->getMockInstance(300, 'ConfigEntityRevisionsRevertFormTest2');
    $this->instance->displayUpdate();
  }

  /**
   * Confirm that a redirection is set up as desired.
   *
   * @test
   */
  public function redirectIsSet() {
    $this->instance = $this->getMockInstance(300, 'ConfigEntityRevisionsRevertFormTest2');

    $form_state = new FormState();
    $this->instance->setRedirect($form_state);

    $redirect = $form_state->getRedirect();
    $this->assertNotNull($redirect);

    $this->assertEquals('entity.my_entity_type.revisions', $redirect->getRouteName());
    $this->assertEquals(['my_entity_type' => 'foozbar'], $redirect->getRouteParameters());
  }

  /**
   * Does the form submission handler call anticipated methods?
   *
   * @test
   */
  public function submitFormCallsAnticipatedMethods() {

    // Start with a revision older than the published / default revision.
    $this->instance = $this->getMockInstance(300, 'ConfigEntityRevisionsRevertFormTest3');
    $form = [];
    $this->instance->submitForm($form, new FormState());

    $this->assertEquals([
      'applyRevisionChange',
      'updateConfigEntity',
      'logUpdate',
      'displayUpdate',
      'setRedirect',
    ], $this->instance->callsMade);
  }

}

/**
 * Main test class - just gives access to otherwise protected values.
 */
class ConfigEntityRevisionsRevertFormTest extends ConfigEntityRevisionsRevertFormBase {

  /**
   * Provide the test class with access to protected values.
   *
   * @param string $value
   *   The value to retrieve.
   *
   * @return mixed
   *   The protected class variable.
   */
  public function get($value) {
    return $this->{$value};
  }

}

/**
 * Override revision preparation so we can test the applyRevisionChange method.
 */
class ConfigEntityRevisionsRevertFormTest2 extends ConfigEntityRevisionsRevertFormTest {

  /**
   * Was prepareRevertedRevision called?
   *
   * @var bool
   */
  protected $prepareRevertedRevisionCalled = FALSE;

  /**
   * Was prepareToPublishCurrentRevision called?
   *
   * @var bool
   */
  protected $prepareToPublishCurrentRevisionCalled = FALSE;

  /**
   * Override prepareRevertedRevision so we can be sure it is actually called.
   *
   * @param \Drupal\config_entity_revisions\ConfigEntityRevisionsEntityInterface $revision
   *   The revision to be published.
   *
   * @return \Drupal\config_entity_revisions\ConfigEntityRevisionsEntityInterface
   *   The resulting revision record, ready to be saved.
   */
  public function prepareRevertedRevision(ConfigEntityRevisionsEntityInterface $revision) {
    $this->prepareRevertedRevisionCalled = TRUE;
    return $revision;
  }

  /**
   * Override prepareToPublishCurrentRevision to be sure it is actually called.
   *
   * @param \Drupal\config_entity_revisions\ConfigEntityRevisionsEntityInterface $revision
   *   The revision to be published.
   *
   * @return \Drupal\config_entity_revisions\ConfigEntityRevisionsEntityInterface
   *   The resulting revision record, ready to be saved.
   */
  public function prepareToPublishCurrentRevision(ConfigEntityRevisionsEntityInterface $revision) {
    $this->prepareToPublishCurrentRevisionCalled = TRUE;
    return $revision;
  }

}

/**
 * Override revision preparation so we can test the applyRevisionChange method.
 */
class ConfigEntityRevisionsRevertFormTest3 extends ConfigEntityRevisionsRevertFormTest {

  /**
   * The list of calls that were made to this class's methods.
   *
   * @var array
   */
  public $callsMade = [];

  /**
   * Apply the revision insert/update.
   */
  public function applyRevisionChange() {
    $this->callsMade[] = 'applyRevisionChange';
  }

  /**
   * Apply the revision insert/update.
   */
  public function updateConfigEntity() {
    $this->callsMade[] = 'updateConfigEntity';
  }

  /**
   * Apply the revision insert/update.
   */
  public function logUpdate() {
    $this->callsMade[] = 'logUpdate';
  }

  /**
   * Apply the revision insert/update.
   */
  public function displayUpdate() {
    $this->callsMade[] = 'displayUpdate';
  }

  /**
   * Apply the revision insert/update.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state to be modified.
   */
  public function setRedirect(FormStateInterface $form_state) {
    $this->callsMade[] = 'setRedirect';
  }

}

/**
 * Mock serialised class.
 *
 * The serializer (sic) we'd normally mock above has deserialize as a final
 * method. Instead of using it, define our own class here.
 */
class MockSerializer {

  /**
   * The test class.
   *
   * @var \Drupal\Tests\config_entity_revisions\Unit\ConfigEntityRevisionsRevertFormBaseTest
   */
  public $testClass = NULL;

  /**
   * Whether the method was called.
   *
   * @var bool
   */
  public $wasCalled = FALSE;

  /**
   * The key/value pairs.
   *
   * @var array
   */
  public $keyValuePairs = [];

  /**
   * Constructor. Store a copy of the test class so we can use its prophecy.
   *
   * @param \Drupal\Tests\config_entity_revisions\Unit\ConfigEntityRevisionsRevertFormBaseTest $testClass
   *   The test class instance.
   */
  public function __construct(ConfigEntityRevisionsRevertFormBaseTest $testClass) {
    $this->testClass = $testClass;
  }

  /**
   * Unserialise settings.
   *
   * @param string $data
   *   Mock serialised data.
   * @param string $type
   *   The unused type argument.
   * @param string $format
   *   The unused format argument.
   * @param array $context
   *   The array of context.
   *
   * @return string
   *   The result.
   *
   * @throws \Exception
   */
  public function deserialize($data, $type, $format, array $context = []) {
    if ($data !== 'serialisedConfiguration') {
      throw new \Exception("Mock Serializer class's deserialize method should be called with data = 'serialisedConfiguration'");
    }

    $this->wasCalled = TRUE;

    $configEntity = $this->testClass->prophesizeProxy(ConfigEntityRevisionsInterface::CLASS);
    $configEntity->enforceIsNew(Argument::type('bool'))
      ->shouldBeCalled()
      ->will(function ($args) {
        if (!$args || $args[0]) {
          throw new \Exception("enforceIsNew wasn't set to FALSE.");
        };
      });

    $configEntity->get(Argument::type('string'))
      ->shouldBeCalledTimes(1)
      ->willReturn('originalSettings');

    $keyValuePairs = &$this->keyValuePairs;
    $configEntity->set(Argument::type('string'), Argument::any())
      ->will(function ($args) use (&$keyValuePairs) {
        $keyValuePairs[$args[0]] = $args[1];
      });

    $configEntity->save()->shouldBeCalled();

    return $configEntity->reveal();
  }

}
