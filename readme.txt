=== PetPress ===
Contributors: brygs
Tags: animal shelter, animals, pets, PetPoint, adoptable pets
Donate link: paypal.me/airdriemedia
Requires at least: 5.7
Tested up to: 6.6
Stable tag: 1.7
Requires PHP: 7.4
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

PetPress allows PetPoint users to create lists and detail pages for animals in their shelter(s).

== Description ==
PetPress empowers PetPoint users to create lists and detail pages for animals in their shelter(s). By using a shortcode, you can list animals in a shelter location by species, or you can show the details of an individual animal.

== Installation ==
1) Contact PetPoint and request that web services be turned on for your organization. <a href="https://pethealth.force.com/community/s/contactsupport">https://pethealth.force.com/community/s/contactsupport</a>. 
2) Install and activate PetPress plug-in.
3) Under settings->PetPress, enter your PetPoint authorization key (found in PetPoint at "Admin > Admin Options > Setup > Online Animal Listing Options").
4) Create a Wordpress page with a shortcode, eg: [PETPRESS species="1"] (to show dogs).
5) See <a href="https://www.airdriemedia.com/petpress">www.airdriemedia.com/petpress</a> for more options.

== Screenshots ==
1. Animal list page
2. Animal detail page
3. Settings page

== Changelog ==
Version 1.7
1) New option so that all animals have their own URLs. This is to facilitate social sharing and bookmarking.
2) New images for when dogs/cats/others are not found (replaces the standard "no photo available" images that PetPoint uses).
3) BUG FIX: Secondary breed now shows alongside primary breed (for animals that have secondary breeds) in the detail pages.

Version 1.6
1) Added option to hide animals that are flagged "on hold".
2) Animal photos now shown in "lightbox" instead of simple links to the Petango image.
3) Added an option to show adoption application links for those shelters that use PetPoint's application process. 
4) Stylesheet now loaded using Wordpress enqueue call instead of being loaded in-line.
5) Removed custom coloring for male/female/unknown from admin settings. Colorization still available by overriding styles.

Version 1.5
1) Cleaned up database tables, reconciled data types
2) Added PetPoint ID and Microchip ID as fields that can appear on detail page.
3) Removed separators between age / sex / weight when there is no data to separate

Version 1.4.3
1) Shortened data refresh interval to 30 minutes (from 60 minutes)
2) BETA Added the ability to set up a "purge cache" page for special cases in which you can't wait for the regular update interval. This is a BETA feature; any and all aspects are subject to change.
3) BETA "volunteer" list, the ability to set up a summary page showing which animals are lacking photos, videos and/or descriptions. This is a BETA feature; any and all aspects are subject to change.

Version 1.4.2
1) Fixed issue with alphanumeric site IDs (if Org ID is erroneously used in place of Site ID should fail gracefully)
2) Added message to admin explaining that PetPress won't output in admin mode
3) Shortened data refresh interval to 60 minutes (from 120 minutes)
4) "Stickies" overlays have been made slightly smaller.
5) BETA "found animals" list. Use shortcode parameter "report=found" and specify species ("0" for all species). This is a BETA feature; any and all aspects are subject to change.

Version 1.4.1
1) Reformatted list page
2) Color accents for male/female optional
3) Admin setting to make Age and Weight on list page either general or specific
4) Admin setting to make video icon optional
5) "In a Foster Home" post-it note for animals whose locations or sublocations contains the word "foster".
6) Restored five column layout for wide screens. Pets per page options changed to accommodate five column layout
7) Drop-down on list page for quick access to an animal by name
8) Restored breed name capitalization to AKC guidelines

Version 1.4:
1) Instead of loading PetPoint data on the first load after the cache expires, loading is now a background task, on two-hour cycle (staggered start times for dogs/cats/other). Removed cache option from admin.
2) Added No Dogs / No Cats / No Kids display option
3) Added Lived with Animals and Lived with Children display option.
4) Added Special Needs as a display option.
5) Added Location / SubLocation as a display option.
5) Added Behavior test result as a display option.
6) Added Reason for surrender as a display option.
7) Randomize photo feature now favors "Photo 1" over the other two photos.
8) Breed name capitalization as per MLA guidelines. 

Version 1.3.2:
1) Added "loading" spinner animation when rosters are built or retrieved.
2) Added other types of animals: small and furry, pigs, reptiles, bird, barnyard. All PetPoint animal types now supported.
3) Added "Adoption Pending" flag for animals whose PetPoint "stage" value begins with "Adopted"
4) Added secondary breed to detail page. Note that if primary breed is a mix, then the secondary breed is not shown.
5) Added the ability to customize the accent colors for male, female, and unknown animals.
6) Fixed Facebook open graph data so that individual animals can be posted in Facebook (though this will be undone by Yoast and perhaps other SEO plugins that set their own canonical URLs)
7) New shortcode parameter, "showsite" will show site on the list and detail pages even if the setting is turned off globally. 
8) Bug fix: Custom cache length may have been ignored in favor of the default.
9) Bug fix: "Other Animals" were being displayed, but they were being called "1003s".
10) Minor CSS changes to avoid wrapping issues with the list view.

Version 1.3.1:
1) Bug fix: Resolves an issue in which animals added/removed from PetPoint were not being added/removed from the PetPress rosters.
2) Minor UI: changed "memo" classname to "pp_memo" to keep class names consistent.

Version 1.3:
1) Improved caching for multi-site organizations.
2) Added Rabbits and Horses as species that can be listed.
3) Added option to randomize the photos used on the list page.
4) Page titles on detail pages list name, species and breed.
5) Removed the auto-generated list headings. Note that the "heading" shortcode parameter can be used to create list headings.
6) To facilitate editing of web pages, the shortcode returns nothing while in the DIVI front-end editor or in admin pages.
7) Additional CSS fixes

Version 1.2.1:
1) Small change to facilitate faster loading of pages in the Wordpress editor.

Version 1.2:
1) Added "site" and "price" as displayable fields.
2) Added "heading" as a shortcode parameter. If specified, this overrides the auto-generated “Dogs at Rescues-R-Us” heading at the top of list pages.
3) New CSS for list pages adjusts the size of the tiles for a better fit.
4) Bug fix: Resolves an issue discovered for websites using the Themify Builder that resulted in the lists of animals sometimes showing each animal twice. It is unknown if the problem extended beyond the Themify Builder.
5) Bug fix: Animal names longer than 14 characters will show 12 characters plus an ellipsis (“…”) so that the list page layout is not disrupted.

Version 1.1:
1) Bug fix: Prior to fix, sometimes PetPoint would be called for an individual animal's record even though there was a cached item recent enough to use instead.
2) Added "sort" as a shortcode parameter. Valid values are "age", "name", and "weight". Default sort is by name. Sort parameter in querystring gets precedence over sort parameter in shortcode.
3) Added "housetrained" and "on hold" as displayable fields.
4) Changed "video" icon from an image to a WordPress dashicon.
5) Added test to make sure that "site" parameter is numeric.
6) Re-stated minimum WP requirement from 5.8 to 5.7 after testing with 5.7.

Version 1.0: Initial production version.