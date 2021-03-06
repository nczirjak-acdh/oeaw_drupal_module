# oeaw_drupal_module - https://acdh.oeaw.ac.at/redmine/issues/6503

# Updated: 11. May. 2017

### Installation
In the drupal/modules directory create a new directory called oeaw and copy the content of the repo. Or clone it.

Turn off the "Internal Dynamic Page Cache" and "Internal Page Cache" modules. Because if you not turn it off then the sidebar search will not work for the anonymus users.

If you finished with the setup then you can reach the modul in the following url: www.yourdomain.com/oeaw_menu

### Module Menu:

  - List All Root Resource
  - Search by Meta data And URI
  - Add New Resource
  
#### List All Root Resource

This menu contains the root resources. The root resource is which has no dct:isPartOf value. The results displayed in the Jquery Datatable, so the result is searchable and ordarable.

You can check the Resources Details or you can edit them at the moment. If a Resource has a children then it will be listed to a second table beyond the root table details. The Childr

#### Search by Meta data And URI
Here you can search by:
  - MetaKey
  - MetaKey and MetaValue
  - MetaKey and URI
  - MetaKey and MetaValue and UR
  
The results displayed in the drupal core tables, but it will be changedt to the datatable too.

#### Add New Resource
This menupoint has a multistep form.

Step 1:
	You can select the Root element (which has no dct:isPartOf property) 
	and the ontology Class (based on the acdh-repo-core/ontology)

Step 2:
	The second step form will generate fields based on the ontology what you selected in the step 1. If Your selected ontology needs a binary resource, then the modul will generate a file upload field.
	After you filled the fields then you can submit your data to the fedora DB -> in this step you basically creating the sparql file with the triples.
	
If everything was okay then you will get a success message with your new fedora db URL.

### SideBar Blocks
#### SideBar Class
Based on the redmine #7397 issue. If the class children has no dc:title, then the children uri will be visible on the results page.
	
#### Sidebar Search 
The "Search by Meta data And URI" menupont sidebar version.
	
	
	How to add a block to your site?
	Go to the a admin/Structure/Block layout (http://yourdomain.com/admin/structure/block)
	Select the Block where you want to add the sidebar and click on the "Place block" button.  After it a new popup window contains the availble blocks, here you can find the two oeaw block:
	"Sidebar Class List OEAW" and "Sidebar Search OEAW". Click the Place Block button and after the Save Block button to add this to the website.

### User restrictions:
Not logged users can't edit or add new resources. They have only browsing permissions.

### Useful infos:
If you have routing problems with the drupal 8 then, try to open the http://yoursite.com/admin/config/development/performance and try to remove the cache. 
If it is not reachable too then you should run the drush cache-rebuild command in your machine. (In our case login to the docker image and run there the command).


# Modifications:

#### 11.05.2017
- version2 branch added because of the new vesion of the repo-php-util 

#### 05.05.2017
- Cardinality added to the New and Edit Forms. Now the form input fields will be generated based on the cardinality values.
- Cardinality types:
-- cardinality -> then 1 input field and it will be required
-- min-cardinality: 2 -> then 2 input field will be generated
-- max-cardinality: 3 -> then 1 input field will be generated and the user will have an add/remove button to add maximum 3 input fields.


#### 19.04.2017
- small bugfixes
- cardinality sql

#### 06.04.2017
- add and edit form bugfix
- sidebar class and search property listing in alphabetical order

#### 31.03.2017
- multiple identifier will be available now in the detail table view.

#### 30.03.2017
- resource delete function added to the detail views
- template bugfixes
- search bugfix - double values
- download icon bugfix

#### 27.03.2017
- New Resource success page created 
- Edit Resource success page created
- Local Drupal config values removed, now the modul using only the config.ini values.

#### 24.03.2017
- EasyRdf Graph Resources label output character coding changed.

#### 17.03.2017
- code refactoring
- detail table image bug fixed
- some small bugfix

#### 16.03.2017
- easyRDF namespace problem first beta check, and sidebar class changes

#### 14.03.2017
- easyRDF updated to the namespaces version and because of this we updated the drupal module too

#### 09.03.2017
- thumbnails extended to the foaf image rdf type too, not just for the thumbnails

#### 07.03.2017
- bugfixes on the search result views
- table property uris changed to prefixes
- lightbox2 added to the layout
- thumbnails now available in child tables, and in the detail views.
- thumbail images using lightbox2


#### 23.02.2017
- bugfixes
- thumbnail development started

#### 20.02.2017
- #8015 resolved

#### 17.02.2017
- sparql query changes
- php 7 optimizations


#### 09.02.2017
- there was a bug in the tablelisting, and if we had multiple elements for one property, then I am used the wrong EasyRdf method. Now i Fixed it.
- #7877 - solved. The list view contained the Fedora button, if it was a binary then after the click action the content was downloaded, otherwise the user was forwareded to the fedora page. 
 Now I am also extended the table views, and now every root resource which is a Binary Resource, has a download icon next to the title.
- Fedora and FedoraResources classes updated and because of this I started to implement the built in queries, to we can avoid the using of sparql queries on the drupal GUI. 

#### 07.02.2017
- Pdf upload allowed
- Datatable entries changed to 25 element/page

#### 06.02.2017
-  Basic Rest API added to the module (#7832)

#### 02.02.2017
- Doorkeeper will generate the identifier automatically (#7348)
- EditForm I added the readonly attribute to the identifier input fields
- AddForm, I removed the identifier input field from the form, because doorkeeper will generate it.


#### 31.01.2017
- EditForm bugfix

#### 26.01.2017
- sparql querys removing from the code and use fedora class(#7349) -> edit view done
- autocomplete old and new value text has some encoding problem, solved
- 

#### 25.01.2017
- Edit Form Autocomplete extended(#7693): new method which is increasing the autocomplete speed, the values has links now.
- Edit form title added
- some sparql query removed and i added fedora and fedora resource methods (oeaw_details, oeaw_search)
- Search Form improvements: Now the search form is not only searchin in the strings, if the searched property is a url, then the module will check the entered value in the title/name/fedoraIdProp
If there is a property whit this label then the module will get the acdh identifier for it, run the search again with the acdh identifier on the given property.

#### 19.01.2017
- Edit Form Autocomplete extended(#7693): the selected value from the Autocomplete will insert the uri to the input field. 
But the selected uri label/title/name will appear below the input field.


#### 17.01.2017
v.1.1 is done:
- shows the dc:title for the users if exists, if not then the "RAW URI"
- pass back the title and uri to the user frontend Form.
- now Autocomplete searches on the following property's:
    - common labels (skos:prefLabel, rdfs:label, foaf:name, dc:title)
    - resource URI's
    - acdhid -> which is "fedoraIdProp" from the drupal config.ini file


#### 16.01.2017
- Autocompletion version 1 added to the Edit Form (#7357)
- Autocompletion version 1 added to the Add new resource Form (#7357)

#### 13.01.2017
- Example AutoCompletion Form added (#7357): You can reach it by: http://yourdomain.com/oeaw_ac_form
- Sparql query changes based on ticket #7349 
- Drupal and Jquery conflicts solved



