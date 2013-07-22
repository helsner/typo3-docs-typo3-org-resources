.. ==================================================
.. FOR YOUR INFORMATION 
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.  ÄÖÜäöüß

.. include:: ../../Includes.txt

=================================
CSS
=================================

.. _css-files:

Files
=====

Most of the css files of pages at http://docs.typo3.org are exactly 
those being used on http://typo3.org. The following css files are 
specific for documentation pages::

	<link rel="stylesheet" href="http://docs.typo3.org/css/t3_org_doc_main.css" type="text/css" />
	<link rel="stylesheet" href="http://docs.typo3.org/css/t3_to_be_integrated.css" type="text/css" />
	<link rel="stylesheet" href="../../_static/pygments.css" type="text/css" />
	<link rel="stylesheet" href="http://docs.typo3.org/css/t3pygments.css" type="text/css" />
	<link rel="alternate stylesheet" href="http://docs.typo3.org/css/t3_org_doc_main_alt_0.css" type="text/css" title="Fixed width" />
	<link rel="alternate stylesheet" href="http://docs.typo3.org/css/t3_org_doc_main_alt_1.css" type="text/css" title="Adaptive width" />
	<link rel="alternate stylesheet" href="http://docs.typo3.org/css/t3_org_doc_main_alt_2.css" type="text/css" title="Style 2" />
	<link rel="alternate stylesheet" href="http://docs.typo3.org/css/t3_org_doc_main_alt_3.css" type="text/css" title="Style 3" />
    
.. _the-doc-namespace:

The Namespace ``.doc``
======================    
    
.. important::

   All rules should have ``.doc`` as ancestor!

   All documentation specific rules should be a descendant__ of ``.doc``.
   This means that they should only match elements that are contained 
   within a ``.doc`` container. The reason for this is that we want to
   keep all documentation specific rules separate from other rules being
   used on `typo3.org <http://typo3.org>`_. Example:

   .. code-block:: css

      .doc .d h1 a,
      .doc h1 a {
          text-decoration: none;
      }

   __ http://www.w3.org/TR/CSS2/selector.html#descendant-selectors
   
.. note::
   
   All documentation specific HTML is placed inside a ``.doc``
   container. Common example:

   .. code-block:: html   

      <body>
         <div class="p doc" id="page">

            ... documentation page html ...

         </div>
      </body>


.. _the-css-folder:
      
The ./css folder
================

Learn about the contents of the css folder at 
http://docs.typo3.org/css\. This css folder is a symlink from 
``/home/mbless/public_html/css`` to
``/home/mbless/HTDOCS/github.com/marble/typo3-docs-typo3-org-resources/css``.

Learn about the history of changes at 
https://github.com/marble/typo3-docs-typo3-org-resources/tree/master/webroot/css\.

On srv123 the repository resides at 
``/home/mbless/HTDOCS/github.com/marble/typo3-docs-typo3-org-resources``. 


t3_org_doc_main.css
-------------------
This is the main css file for the documentation specific css.


t3_to_be_integrated.css
-----------------------
This file shouldn't exist. It holds only a few styles that should go 
into :file:`t3_org_doc_main.css`.


pygments.css
------------
This file is included by the Sphinx documentation builder.


t3pygments.css
--------------

**ToDo:** Currently (2013-07-22) This file is a first release of a
highlighting style developed by Michiel Roos to make TypoScript better
visible. It should be checked and improved to make :file:`pygments.css`
superflous.

**ToDo:** Add ancestor ``.doc`` to all styles in :file:`t3pygments.css`.


t3_org_doc_main_alt_0.css
-------------------------

These files are alternate stylesheets. Only one of them is active at a
time. They work in conjunction with the Javascript style switcher on 
the page:

.. code-block:: text

    t3_org_doc_main_alt_0.css
    t3_org_doc_main_alt_1.css
    t3_org_doc_main_alt_2.css
    t3_org_doc_main_alt_3.css


.. toctree: :
   :maxdepth: 5
   :glob:
   :titlesonly:

   *
