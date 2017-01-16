# oeaw_drupal_module - https://acdh.oeaw.ac.at/redmine/issues/6503
Updated: 16. Jan. 2017


1/. Installation:

In the drupal/modules directory create a new directory called oeaw and copy the content of the repo. Or clone it.
After the installation you need to go to the admin/Configuration/OEAW Admin FORM menupont (http://yourdomain.com/admin/config/system/oeaw-admin)

Here you need to add the Sparql endpoint url and the Fedora Url, without these urls the modul will not work.

Turn off the "Internal Dynamic Page Cache" and "Internal Page Cache" modules. Because if you not turn it off then the sidebar search will not work for the anonymus users.

If you finished with the setup then you can reach the modul in the following url: www.yourdomain.com/oeaw_menu

2/. Modul Menu:


	A/ List All Root Resource
	B/ Search by Meta data And URI
	C/ Add New Resource


A/ List All Root Resource:

	This menu contains the root resources. The root resource is which has no dct:isPartOf value. The results displayed in the Jquery Datatable, so the result is searchable and ordarable.
	You can check the Resources Details or you can edit them at the moment. If a Resource has a children then it will be listed to a second table beyond the root table details. The Children has the same details and edit
	actions.
	

B/ Search by Meta data And URI:

	Here you can find by:
	- MetaKey
	- MetaKey and MetaValue
	- MetaKey and URI
	- MetaKey and MetaValue and URI
	
	The results displayed in the drupal core tables, but it will be changedt to the datatable too.


C/ Add New Resource: 

	This menupoint has a multistep form.
	
	Step 1:
	You can select the Root element (which has no dct:isPartOf property) 
	and the ontology Class (based on the acdh-repo-core/ontology)

	Step 2:
	The second step form will generate fields based on the ontology what you selected in the step 1. If Your selected ontology needs a binary resource, then the modul will generate a file upload field.
	After you filled the fields then you can submit your data to the fedora DB -> in this step you basically creating the sparql file with the triples.
	
	If everything was okay then you will get a success message with your new fedora db URL.
	


3/. SideBar Blocks

	A/ SideBar Class
	 Based on the redmine #7397 issue. If the class children has no dc:title, then the children uri will be visible on the results page.
	
	B/ Sidebar Search 
	The "Search by Meta data And URI" menupont sidebar version.
	
	
	How to add a block to your site?
	Go to the a admin/Structure/Block layout (http://yourdomain.com/admin/structure/block)
	Select the Block where you want to add the sidebar and click on the "Place block" button.  After it a new popup window contains the availble blocks, here you can find the two oeaw block:
	"Sidebar Class List OEAW" and "Sidebar Search OEAW". Click the Place Block button and after the Save Block button to add this to the website.

4/. User restrictions:
	Not logged users can't edit or add new resources. They have only browsing permissions.
	




Modifications:

16.01.2017/.
	1/ Autocompletion version 1 added to the Edit Form (#7357)

	2/ Autocompletion version 1 added to the Add new resource Form (#7357)

13.01.2017/.
	1/ Example AutoCompletion Form added (#7357): You can reach it by: http://yourdomain.com/oeaw_ac_form

	2/ Sparql query changes based on ticket #7349 

	3/ Drupal and Jquery conflicts solved