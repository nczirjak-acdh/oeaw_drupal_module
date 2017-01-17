# oeaw_drupal_module - https://acdh.oeaw.ac.at/redmine/issues/6503

# Updated: 17. Jan. 2017
### Installation
In the drupal/modules directory create a new directory called oeaw and copy the content of the repo. Or clone it.
After the installation you need to go to the admin/Configuration/OEAW Admin FORM menupont (http://yourdomain.com/admin/config/system/oeaw-admin)

Here you need to add the Sparql endpoint url and the Fedora Url, without these urls the modul will not work.

Turn off the "Internal Dynamic Page Cache" and "Internal Page Cache" modules. Because if you not turn it off then the sidebar search will not work for the anonymus users.

If you finished with the setup then you can reach the modul in the following url: www.yourdomain.com/oeaw_menu

### Modul Menu:

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

# Modifications:
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



