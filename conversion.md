# Hook conversion acceptance criteria

- [ ] Upon installation from this branch, visiting `/admin/config/workflow/workflows/manage/standard_workflow` shows the Event node included as using the Standard Workflow, establishing that `Drupal\utevent_content_type_event\Hooks::addStandardWorkflow()` is working
- [ ] The `utevent` main module and the four sub-modules where conversion took place all have a `*.services.yml` declaration for `skip_procedural_hook_scan: true`
- [ ] When "Automatically archive past events" is enabled (`/admin/config/content/utevent`) and a published Event's date ends prior to 30 days in the past, when cron runs (`lando drush cron`), the Event is unpublished and its moderation state is set to 'Archived,' establishing that `Drupal\utevent_content_type_event\Hooks::cron()` is working.
- [ ] After enabling `utevent_demo_content`, viewing an individual event and the Event listing `/events` shows expected look and feel, establishing that `Drupal\utevent_content_type_event\Hooks::theme()` and `Drupal\utevent_content_type_event\Hooks::themeSuggestionsField()` is working.
- [ ] When an Event's status is set to "Canceled," it displays the "Canceled" pill, establishing that `Drupal\utevent_content_type_event\Hooks::preprocessNode()` is working.
- [ ] Viewing an event node with associated taxonomy terms (`/events/quotidie-septem`) shows them rendering in the sidebar, establishing that `Drupal\utevent_content_type_event\Hooks::preprocessField()` is working
- [ ] After creating a Flex Page, the "Event listing" block is not listed in the settings tray as a reusable block (it **should** be available in "Create custom block", just not in the generic settings tray), demonstrating that `Drupal\utevent_block_type_event_listing\Hooks::pluginFilterBlockLayoutBuilderAlter()` is working
- [ ] After creating a Flex Page and placing an **inline** "Event listing block on the page, the event listings render using `utevent_block_type_event_listing/templates/block--inline-block--utevent-event-listing.html.twig`, establishing that `Drupal\utevent_block_type_event_listing\Hooks::themeSuggestionsBlockAlter()` and `theme()` are working
- [ ] When the same block is configured to "Display link back to main events page", a "View all events" button is rendered, establishing that `Drupal\utevent_block_type_event_listing\Hooks::preprocessBlock()` is working
 and `preprocessViewsViewFields()` are working
- [ ] On the block listing, events that are non-recurring do not display the "narrative" (e.g., `Recurs: Weekly on...`) establishing that `Drupal\utevent_block_type_event_listing\Hooks::preprocessViewsViewFields()` is working
- [ ] Navigating to `/admin/structure/types/manage/utevent_event` shows the `The Event add-on is read-only and may not be changed.` message, demonstrating that `Drupal\utevent_readonly\Hooks::formAlter()` and `pageAttachments()` are working
- [ ] Navigating to `/admin/structure/types` shows the `View fields (read only)` for the Event content type, demonstrating that `Drupal\utevent_readonly\Hooks::entityOperationAlter()` is working
- [ ] On `/events`, events that are non-recurring do not display the "narrative" (e.g., `Recurs: Weekly on...`) establishing that `Drupal\utevent_view_listing_page\Hooks::preprocessViewsViewFields()` is working
- [ ] On `/past-events`, the "Calendar | Upcoming Events | Past Events" show "Past Events" as active, demonstrating that `Drupal\utevent_view_listing_page\Hooks::viewsPreView()` is working

## utevent_view_listing_page

### Listing page view field preprocess
Acceptance criteria:
- Given the Event listing page renders a recurring Event, when the datetime narrative field appears, then its content is plain text without HTML tags.
- Given the Event listing page renders a non-recurring Event, when the row renders, then the datetime narrative field is hidden.

### General configuration form alter
Acceptance criteria:
- Given an administrator opens the Event general configuration form, when the form renders, then the listing page title configuration fields and submit handling are present.

### Views pre-view
Acceptance criteria:
- Given calendar display is disabled, when the upcoming or past listing page displays render, then the Calendar button is not shown.
- Given past events are disabled, when the upcoming listing page or calendar page renders, then Past Events controls are not shown.
- Given past events are enabled, when the upcoming listing page or calendar page renders, then the correct Past Events control is shown.

### Exposed form alter
Acceptance criteria:
- Given the Event listing page or calendar page has one or fewer Location options, when exposed filters render, then the Location filter is hidden.
- Given the Event listing page or calendar page has one or fewer Type options, when exposed filters render, then the Type filter is hidden.
- Given both Location and Type filters are hidden, when exposed filters render, then the form actions are hidden.

### JavaScript settings alter
Acceptance criteria:
- Given the Event calendar page renders FullCalendar events, when assistive technology reads an event title, then the event date is prepended in visually hidden text.
