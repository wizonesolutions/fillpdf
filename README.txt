see http://drupal.org/node/369990 for the basics.

Local Tomcat setup
------------------
If you have a VPS or dedicated server and you'd rather install the iText service locally, follow these instructions:

* Install PHP/Java-bridge on your same Drupal server by deploying the JavaBridge.war on Tomcat: http://php-java-bridge.sourceforge.net/pjb/installation.php
* Download latest iText.jar from http://itextpdf.com/, and move it to $TOMCAT_DIRECTORY/webapps/JavaBridge/WEB-INF/lib
* Do the same for FillpdfService.jar, from http://github.com/downloads/lefnire/fillpdf-service/FillpdfService.jar
* Symlink or copy your JavaBridge webapp directory into fillpdf/lib. (eg, ln -s $TOMCAT_DIR/webapps/JavaBridge $DRUPAL_SITE/sites/all/modules/fillpdf/lib/JavaBridge)
