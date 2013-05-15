Docalist : 
    - container en chef
    - TODO : c'est un container qui peut contenir :
        - Plugin (tous les plugins docalist actuellement chargés)

Plugin :
    - c'est un Registrable (container = Docalist)
    - c'est un container qui peut contenir :
        - PostType
        - Taxonomy
        - AdminPage
        - Settings
        - SettingsPage
        
PostType :
    - c'est un Registrable (container = Plugin)
    - c'est un container qui peut contenir :
        - Metabox
        
Taxonomy :
    - c'est un Registrable (container = Plugin)
    - TODO : c'est un container qui peut contenir :
        - Metabox
        
Settings :
    - c'est un Registrable (container = Plugin)

AdminPage :
    - c'est un Registrable (container = Plugin)

SettingsPage :

Metabox :
    - c'est un Registrable (container = Plugin, PostType ou Taxonomy)
    - TODO : actuellement, plugin uniquement. 
      Idéalement : 
      Container = PostType : register auto, écran d'édition des notices
      Container = Taxonomy : register auto, écran d'édition des termes
      Container = Plugin   : register manuel (exemple : profile user)

--------------------------------------------------------------------------------
AdminActions :
    - Enregistrées via un appel de la forme 
        add_action('admin_action_' . $this->id(), function() {...});
    
    - Par exemple : "admin_action_docalist-search-actions" pour la 
        classe "Actions" du plugin docalist-search.
    
    - Peuvent être appellées via un appel de la forme : 
        wordpress/wp-admin/admin.php?action=$this->id()
    
    - Par exemple, pour la classe Actions de docalist-search, ça donne :
        http://prisme/wordpress/wp-admin/admin.php?action=docalist-search-actions
    
    - Attention, l'argument wordpress s'appelle "action", pas "page".
    
AdminPage :    