<?php

namespace Drupal\utevent;

use Drupal\user\Entity\Role;

/**
 * Provided pre-defined permissions for utevent.
 */
class Permissions {

  /**
   * Permissions associated with add-on management.
   *
   * @var array
   */
  public static $manager = [
    'administer utevent',
  ];

  /**
   * Permissions associated with content editing.
   *
   * @var array
   */
  public static $editor = [
    'cancel smart date recur instances',
    'create terms in utevent_location',
    'create terms in utevent_tags',
    'create utevent_event content',
    'delete any utevent_event content',
    'delete own utevent_event content',
    'delete terms in utevent_location',
    'delete terms in utevent_tags',
    'delete utevent_event revisions',
    'edit any utevent_event content',
    'edit own utevent_event content',
    'edit terms in utevent_location',
    'edit terms in utevent_tags',
    'make smart dates recur',
    'reschedule smart date recur instances',
    'revert utevent_event revisions',
    'view utevent_event revisions',
  ];

  /**
   * Manipulate an array provided by this class for use in an HTML table.
   *
   * @param array $array
   *   A permissions array defined in this class.
   *
   * @return array
   *   A simple two-value array.
   */
  public static function displayPermissions(array $array) {
    $available_permissions = \Drupal::service('user.permissions')->getPermissions();
    $rows = [];
    foreach ($array as $name) {
      if (in_array($name, array_keys($available_permissions))) {
        $rows[] = [$available_permissions[$name]['title'], $name];
      }
    }
    return $rows;
  }

  /**
   * Retrieve a subset of roles in the system.
   *
   * @return array
   *   An array of Drupal role objects.
   */
  public static function getAllowedRoles() {
    $roles = \Drupal::entityTypeManager()->getStorage('user_role')->loadMultiple();
    // Do not allow assigning these permissions to `anonymous`/`authenticated`.
    unset($roles['anonymous']);
    unset($roles['authenticated']);
    return $roles;
  }

  /**
   * Save a set of permissions to a given role.
   *
   * @param string $set
   *   The internal set (either 'manager' or 'editor')
   * @param string $role
   *   A valid Drupal role machine name.
   *
   * @return bool
   *   Whether or not the save was successful.
   */
  public static function assignPermissions($set, $role) {
    $available_permissions = \Drupal::service('user.permissions')->getPermissions();
    if (!$role = Role::load($role)) {
      return FALSE;
    }
    foreach (self::$$set as $permission) {
      if (in_array($permission, array_keys($available_permissions))) {
        $role->grantPermission($permission);
      }
    }
    return $role->save();
  }

}
