-- *************************************************************************--
--                                                                          --
--                                                                          --
-- Model migration script - 2301.3.0 to 2301.3.6                            --
--                                                                          --
--                                                                          --
-- *************************************************************************--
--DATABASE_BACKUP|parameters

-- La requête n'insérera la nouvelle ligne que s'il n'y a pas de ligne existante avec l'identifiant égal à allowMultipleAvisAssignment.
-- Si une ligne avec cet identifiant existe déjà, l'instruction INSERT ne s'exécutera pas.
INSERT INTO parameters (id, description, param_value_string, param_value_int, param_value_date)
SELECT 'allowMultipleAvisAssignment', 'Un utilisateur peut fournir plusieurs avis tout en conservant le même rôle', NULL, 0, NULL
    WHERE NOT EXISTS (
    SELECT 1 FROM parameters WHERE id = 'allowMultipleAvisAssignment'
);
