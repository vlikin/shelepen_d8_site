<?php

/**
 * @file
 * Contains \Drupal\page_manager\Entity\Page.
 */

namespace Drupal\page_manager\Entity;

use Drupal\page_manager\PageInterface;
use Drupal\Core\Condition\ConditionPluginCollection;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\page_manager\Plugin\VariantAwareTrait;

/**
 * Defines a Page entity class.
 *
 * @ConfigEntityType(
 *   id = "page",
 *   label = @Translation("Page"),
 *   handlers = {
 *     "access" = "Drupal\page_manager\Entity\PageAccess",
 *     "list_builder" = "Drupal\page_manager\Entity\PageListBuilder",
 *     "view_builder" = "Drupal\page_manager\Entity\PageViewBuilder",
 *     "form" = {
 *       "add" = "Drupal\page_manager\Form\PageAddForm",
 *       "edit" = "Drupal\page_manager\Form\PageEditForm",
 *       "delete" = "Drupal\page_manager\Form\PageDeleteForm",
 *     }
 *   },
 *   admin_permission = "administer pages",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "status" = "status"
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/page_manager/add",
 *     "edit-form" = "/admin/structure/page_manager/manage/{page}",
 *     "delete-form" = "/admin/structure/page_manager/manage/{page}/delete",
 *     "enable" = "/admin/structure/page_manager/manage/{page}/enable",
 *     "disable" = "/admin/structure/page_manager/manage/{page}/disable"
 *   }
 * )
 */
class Page extends ConfigEntityBase implements PageInterface {

  use VariantAwareTrait;

  /**
   * The ID of the page entity.
   *
   * @var string
   */
  protected $id;

  /**
   * The label of the page entity.
   *
   * @var string
   */
  protected $label;

  /**
   * The path of the page entity.
   *
   * @var string
   */
  protected $path;

  /**
   * The configuration of the display variants.
   *
   * @var array
   */
  protected $display_variants = [];

  /**
   * The configuration of access conditions.
   *
   * @var array
   */
  protected $access_conditions = [];

  /**
   * Tracks the logic used to compute access, either 'and' or 'or'.
   *
   * @var string
   */
  protected $access_logic = 'and';

  /**
   * The plugin collection that holds the access conditions.
   *
   * @var \Drupal\Component\Plugin\LazyPluginCollection
   */
  protected $accessConditionCollection;

  /**
   * Indicates if this page should be displayed in the admin theme.
   *
   * @var bool
   */
  protected $use_admin_theme;

  /**
   * Stores a reference to the executable version of this page.
   *
   * This is only used on runtime, and is not stored.
   *
   * @var \Drupal\page_manager\PageExecutable
   */
  protected $executable;

  /**
   * Returns a factory for page executables.
   *
   * @return \Drupal\page_manager\PageExecutableFactoryInterface
   */
  protected function executableFactory() {
    return \Drupal::service('page_manager.executable_factory');
  }

  /**
   * {@inheritdoc}
   */
  public function getExecutable() {
    if (!isset($this->executable)) {
      $this->executable = $this->executableFactory()->get($this);
    }
    return $this->executable;
  }

  /**
   * {@inheritdoc}
   */
  public function toArray() {
    $properties = parent::toArray();
    $names = [
      'id',
      'label',
      'path',
      'display_variants',
      'access_conditions',
      'access_logic',
      'use_admin_theme',
    ];
    foreach ($names as $name) {
      $properties[$name] = $this->get($name);
    }
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function getPath() {
    return $this->path;
  }

  /**
   * {@inheritdoc}
   */
  public function usesAdminTheme() {
    return isset($this->use_admin_theme) ? $this->use_admin_theme : strpos($this->getPath(), '/admin/') === 0;
  }

  /**
   * {@inheritdoc}
   */
  public function postCreate(EntityStorageInterface $storage) {
    parent::postCreate($storage);
    // Ensure there is at least one display variant.
    if (!$this->getVariants()->count()) {
      $this->addVariant([
        'id' => 'http_status_code',
        'label' => 'Default',
        'weight' => 10,
      ]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);
    static::routeBuilder()->setRebuildNeeded();
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);
    static::routeBuilder()->setRebuildNeeded();
  }

  /**
   * Wraps the route builder.
   *
   * @return \Drupal\Core\Routing\RouteBuilderInterface
   *   An object for state storage.
   */
  protected static function routeBuilder() {
    return \Drupal::service('router.builder');
  }

  /**
   * Wraps the config factory.
   *
   * @return \Drupal\Core\Config\ConfigFactoryInterface
   *   The config factory.
   */
  protected function configFactory() {
    return \Drupal::service('config.factory');
  }

  /**
   * {@inheritdoc}
   */
  protected function getVariantConfig() {
    return $this->get('display_variants');
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return [
      'display_variants' => $this->getVariants(),
      'access_conditions' => $this->getAccessConditions(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessConditions() {
    if (!$this->accessConditionCollection) {
      $this->accessConditionCollection = new ConditionPluginCollection(\Drupal::service('plugin.manager.condition'), $this->get('access_conditions'));
    }
    return $this->accessConditionCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function addAccessCondition(array $configuration) {
    $configuration['uuid'] = $this->uuidGenerator()->generate();
    $this->getAccessConditions()->addInstanceId($configuration['uuid'], $configuration);
    return $configuration['uuid'];
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessCondition($condition_id) {
    return $this->getAccessConditions()->get($condition_id);
  }

  /**
   * {@inheritdoc}
   */
  public function removeAccessCondition($condition_id) {
    $this->getAccessConditions()->removeInstanceId($condition_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessLogic() {
    return $this->access_logic;
  }

  /**
   * {@inheritdoc}
   */
  public function getContexts() {
    return $this->getExecutable()->getContexts();
  }

  /**
   * {@inheritdoc}
   */
  public function isFallbackPage() {
    $fallback_page = $this->configFactory()->get('page_manager.settings')->get('fallback_page');
    return $this->id() == $fallback_page;
  }

}
