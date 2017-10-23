# run_manager
RunManager for RRS-OACIS

# Template of RRS-OACIS App
## Packeage namespace rule
`rrsoacis\apps\[Github Username]\[Repository Name]`

e.g. `rrsoacis\apps\tkmnet\run_manager`

- if your username contains dash(`-`), replace dash(`-`) to underscore(`_`) in the namespace.

## manifest.json
- name : "{App name}"
- version : "{Major}.{Minor}.{Revision}"
- description : "{App description}"
- icon : "{App simbol mark}" (Select from http://fontawesome.io/icons/ )
- main_controller : "{Full class name of main page}"
- sub_controller : [ ["{Url suffix}" : "{Full class name of sub page}"] ]
- dependencies : [ ["{Dependence package name}" : "{Minimum of version}"] ]

## URL
- Main page

  `[RRS-OACIS Root]/app/[Github Username]/[Repository Name]`
 
- Sub page

  `[RRS-OACIS Root]/app/[Github Username]/[Repository Name]-{Url suffix}`
  
