-- SUMMARY --
This module populates existing PDF forms with token-specified content.  Think of TurboTax or FAFSA, where
the user is guided through many webforms of data-collection and finally prints out those data into PDFs of 
specific formats.  In these situations (common when working with government agencies), a PDF is provided
by an agency and its  format is strict. Therefore, unlike print.module, which uses dompdf to print 
a page as it looks in print-view, this module requires an existing PDF with form-fields, and will populate 
those form-fields with data. 

-- Dependencies --
Content & Token


-- INSTALLATION --
Install via sites/all/modules > Other > Fill PDF

-- USAGE --

This module requires that you setup links of a specific format in order to download the PDFs (see the last step)
--- setting things up ---
* Go to /admin/content/fillpdf
* Click the "Add PDF" tab
* Enter the PDF's URL, and a title for this form.  Make sure the PDF document isn't encrypted.  If it is 
  encrypted and non copy-righted (typical of government PDFs), then try a decrypting tool like "Advanced 
  PDF Password Recovery."  If you upload an encrypted PDF, you will have empty PDFs when you attempt to 
  download your submissions. 
* Either click "Generate Fields From PDF" or "Add Field" to get fields into the form
* Add field values, mostly you'll be adding tokens
-- downloading the PDF ---
  When you want to print your form to PDF, you need to navigate to /fillpdf?fid=10&nid=10
  where fid is the form id of the form you've just created, and nid is the node id whose content you'll 
  be pulling via tokens.  You can obtain fid from the URL when editing your form.  It will look like:
  http://localhost/admin/content/fillpdf/form/FID/...

-- iText Servlet --
This module depends on iText.  Currently, I have a servlet installed on my home server that handles the
iText functions via remote calls.  If your PDF needs are intensive, you're likely to crash my server; therefore,
please email me and I'll give you the WAR so you can install the servlet on your own server and tell you what
changes you need to make to the module to get that working.
I'd like to move away from servlets and get the PDF functionality into PHP, but there doesn't seem to be any
PHP libraries that support PDF form-field parsing or XFDF-to-PFD merging and flattening, both which are necessary
for this module to work.  If anyone knows of a solution, please email me.

-- CONTACT --
tylerrenelle@gmail.com