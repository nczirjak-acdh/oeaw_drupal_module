# oeaw_drupal_module - https://acdh.oeaw.ac.at/redmine/issues/6503
Updated: 02. December. 2016


1. Installation:

In the drupal/modules directory create a new directory called oeaw and copy the content of the repo. Or clone it.
After the installation the site admin needs to go the the following menu:

	Admin/Configuration/OEAW Admin FORM
	(http://yourdomain.com/admin/config/system/oeaw-admin)

	Here you need to add the Sparql Endpoint URL and The Fedora URL. 
	

After the installation You can reach the modul in the following url: www.yourdomain.com/oeaw_menu

2. Modul Menu:


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
	
	
3. SideBar Search

	You can add a sidebar search block. For this go to the Admin/Structure/Block layout page. Here select the block where you want to add the searchbar. And then select the "Sidebar Search OEAW" from the list.
	
	


