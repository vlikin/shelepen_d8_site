<?php

/**
 * @file
 * Contains \Drupal\Tests\page_manager\Unit\PageExecutableTest.
 */

namespace Drupal\Tests\page_manager\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\page_manager\Event\PageManagerContextEvent;
use Drupal\page_manager\PageExecutable;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the PageExecutable.
 *
 * @coversDefaultClass \Drupal\page_manager\PageExecutable
 *
 * @group PageManager
 */
class PageExecutableTest extends UnitTestCase {

  /**
   * @var \Drupal\page_manager\PageInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $page;

  /**
   * @var \Drupal\page_manager\PageExecutable
   */
  protected $exectuable;

  /**
   * {@inheritdoc}
   *
   * @covers ::__construct
   */
  protected function setUp() {
    parent::setUp();
    $this->page = $this->getMock('Drupal\page_manager\PageInterface');
    $this->exectuable = new PageExecutable($this->page);
  }

  /**
   * @covers ::getPage
   */
  public function testGetPage() {
    $this->assertSame($this->page, $this->exectuable->getPage());
  }

  /**
   * @covers ::selectDisplayVariant
   */
  public function testSelectDisplayVariant() {
    $event_dispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
    $container = new ContainerBuilder();
    $container->set('event_dispatcher', $event_dispatcher);
    \Drupal::setContainer($container);

    $display_variant1 = $this->getMock('Drupal\Core\Display\VariantInterface');
    $display_variant1->expects($this->once())
      ->method('access')
      ->will($this->returnValue(FALSE));
    $display_variant1->expects($this->never())
      ->method('setExecutable');

    $display_variant2 = $this->getMock('Drupal\page_manager\Plugin\PageAwareVariantInterface');
    $display_variant2->expects($this->once())
      ->method('access')
      ->will($this->returnValue(TRUE));
    $display_variant2->expects($this->once())
      ->method('setExecutable')
      ->with($this->exectuable)
      ->will($this->returnValue($display_variant2));
    $this->page->expects($this->once())
      ->method('getVariants')
      ->will($this->returnValue([
        'variant1' => $display_variant1,
        'variant2' => $display_variant2,
      ]));

    $this->assertSame($display_variant2, $this->exectuable->selectDisplayVariant());
  }

  /**
   * @covers ::addContext
   */
  public function testAddContext() {
    $context = new Context(new ContextDefinition('bar'));
    $this->exectuable->addContext('foo', $context);
    $contexts = $this->exectuable->getContexts();
    $this->assertSame(['foo' => $context], $contexts);
  }

  /**
   * @covers ::getContexts
   */
  public function testGetContexts() {
    $context = new Context(new ContextDefinition('bar'));
    $event_dispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
    $event_dispatcher->expects($this->once())
      ->method('dispatch')
      ->will($this->returnCallback(function ($event_name, PageManagerContextEvent $event) use ($context) {
        $event->getPageExecutable()->addContext('foo', $context);
      }));

    $container = new ContainerBuilder();
    $container->set('event_dispatcher', $event_dispatcher);
    \Drupal::setContainer($container);

    $contexts = $this->exectuable->getContexts();
    $this->assertSame(['foo' => $context], $contexts);
  }

}
