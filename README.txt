Installation
------------

This module requires one of several external PDF manipulation tools. You can:

   1. Deploy locally -- You'll need VPS or a dedicated server so you can deploy PHP/JavaBridge on Tomcat (see later section), or
   2. Sign up for Fillpdf as-a-service [1], and plug your API key into /admin/settings/fillpdf
   3. Install pdftk on your server and have that be used for PDF manipulation

Usage
-----

There are three steps to setting up a form with Fill PDF: (1) creating the webform/content-type, (2) mapping it to the PDF, (3) using a URL to merge the two.

   1. To create the webform/content-type wherein users will enter data. Options:
          * Use CCK
          * Use webform (see #374121: Webform Support)

   2. To map the webform to a PDF, do the following:
         1. Go to /admin/structure/fillpdf
         2. Upload a PDF template, a form mapping will be generated [2]
         3. When editing fields, note the following:
                * "Label" is for your own use in identifying fields
                * "PDF Key" is the field-name from the original PDF Form (such as text_field_1) and is the piece that maps the form-field to the PDF field
                * "Value" is where you either enter static data to populate the field, or token-data to pull information form the users' forms. For example, if I created a CCK form with a text-field called field_first_name, then I would enter [field-field_first_name-raw] here. There is a list of tokens you can use at the bottom of that page.

   3. Once your user fills a form, they'll need a link to download their PDF.  You can place this link in a block, .tpl.php, or anywhere.
      The link will need (1) The form-id (you can see an example URL on your form's edit-page), (2) the node-id, and/or (3) the webform's node-id and optionally its submission-id (defaults to latest-submission if none provided)
      Here are some ways to generate the link:
          * Add the link in PHP (recommended).  Examples:
                * One-node link: <?php echo l("PDF", fillpdf_pdf_link($form_id = 1, $node_id = 2)); ?>
                * One-webform link (common): <?php echo l("PDF", fillpdf_pdf_link($form_id = 1, null, $webform = array('nid'=>3,'sid'=>4))); ?>
                * Multiple nodes & webforms, later nids override conflicting fields (note: webforms without 'sid' default to latest submission)
                   <?php echo l("PDF", fillpdf_pdf_link($form_id = 1, $nids = array(1,2), $webforms = array( array('nid'=>3,'sid'=>1), array('nid'=>3))); ?>
          * Add the link manually in HTML. Examples:
                * One-node link: <a href="/fillpdf&fid=1&nid=2">PDF</a> [3]
                * One-webform link: <a href="/fillpdf&fid=1&webform[nid]=3&webform[sid]=4">PDF</a>
                * Multiple nodes & webforms, later nids override conflicting fields (note: webforms without 'sid' default to latest submission)
                   <a href="/fillpdf&fid=1&nids[]=1&nids[]=2&webforms[0][nid]=3&webforms[0][sid]=1&webforms[1][nid]=3">PDF</a>

Notes:
  [1] http://fillpdf-service.com
  [2] Make sure the PDF document isn't encrypted. If it is encrypted and non copy-righted (typical of government PDFs), then try a decrypting tool like "Advanced PDF Password Recovery". If you upload an encrypted PDF, you will have empty PDFs when you attempt to download your submissions.
  [3] If clean URLs is not enabled, the URL will be in the format: /?q=fillpdf&fid=10&nid=10


Local Tomcat setup (optional)
-----------------------------
If you have a VPS or dedicated server and you'd rather install the iText service locally than use Fillpdf as-a-service, follow these instructions:

   1. Install Tomcat (or any Java Servlet Container).  Some pointers, if installing Tomcat on Ubuntu 9.10, webapps seem to not deploy without setting TOMCAT6_SECURITY=no in /etc/default/tomcat6.  Tell me if you find an alternative.
   2. Install PHP/Java-bridge on your same Drupal server by deploying the JavaBridge.war on Tomcat: http://php-java-bridge.sourceforge.net/pjb/installation.php.  Click the "Download" link, which downloads the documentation/examples -- just extract JavaBridge.war
   3. Download latest iText.jar from http://itextpdf.com/, and move it to $TOMCAT_DIRECTORY/webapps/JavaBridge/WEB-INF/lib
   4. Do the same for FillpdfService.jar, from http://github.com/downloads/lefnire/fillpdf-service/FillpdfService.jar
   5. Symlink or copy your JavaBridge webapp directory into fillpdf/lib. (eg, ln -s $TOMCAT_DIR/webapps/JavaBridge $DRUPAL_SITE/sites/all/modules/fillpdf/lib/JavaBridge)
   6. Start Tomcat, then go to /admin/settings/fillpdf & tick the "Use Local Service" checkbox

Local pdftk installation (optional)
-----------------------------
As an alternative to using Tomcat and JavaBridge, you can use the pdftk program, which is installable on most servers via their package managers (e.g. yum install pdftk, apt-get install pdftk, etc.). You may also be able to find tutorials on the Internet to enable you to install this on shared hosting; additional steps may be required in that case. Once you have installed pdftk, make sure it is in your PATH, and you should then find it works automatically.
