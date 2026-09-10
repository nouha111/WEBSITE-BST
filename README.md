# Build Station website work

This repository currently contains the Build Station Catalogues WordPress plugin source. It does not yet contain the existing staging website theme or a complete website redesign.

## Install

Download this repository with Code > Download ZIP, extract it, then ZIP only the `buildstation-catalogues` directory. Upload that plugin ZIP under WordPress > Plugins > Add New > Upload Plugin, and activate on staging first.

## Manage catalogues

Open Catalogues > Add New Catalogue. Enter a title, brand name, logo, edition and PDF. Set a Featured Image for the cover and choose a Segment. Publish when ready. Use Duplicate in the catalogue list to create a draft copy, replace its details, and publish.

Place `[buildstation_catalogues]` in a Shortcode block or Elementor Shortcode widget. On the sanitary ware page use `[buildstation_catalogues segment="sanitary-ware"]`.

Each catalogue has its own page with PDF viewing and a download link. A separate brand directory with multiple catalogues grouped per brand is not implemented yet. Download behavior depends on browser PDF handling. The supplied Duravit catalogue is not bundled; upload it through WordPress Media.

## Validation status

ZIP integrity was checked. PHP runtime linting and WordPress integration testing have not yet been completed. Test activation, saving, duplication, viewing and downloads on staging before production use.

## Complete the redesign

Add the existing WordPress theme/child-theme source and relevant Elementor templates to this repository. Do not commit passwords, wp-config.php, database exports, API keys or private customer data. Adding code here does not automatically deploy it to the staging website.
