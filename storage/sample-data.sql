-- ====================================================
-- Sample data for testing the contact system
-- Données de test pour le système de contact
-- ====================================================

USE traiteur;

-- Insert sample contact requests
INSERT INTO contact_requests (name, email, phone, people, date, location, type, message, status, created_at) 
VALUES 
(
    'Sophie Martin',
    'sophie.martin@example.com',
    '+33 6 12 34 56 78',
    80,
    '2026-07-15',
    'Château de Chantilly',
    'mariage',
    'Bonjour, nous organisons notre mariage et souhaitons un service traiteur pour 80 personnes. Nous aimerions un cocktail apéritif suivi d\'un buffet froid. Merci de nous faire parvenir un devis.',
    'new',
    '2026-02-20 10:30:00'
),
(
    'Jean Dupont',
    'jean.dupont@entreprise-abc.fr',
    '+33 6 98 76 54 32',
    50,
    '2026-06-10',
    'Salle de conférence - Paris',
    'entreprise',
    'Nous organisons un séminaire d\'entreprise et cherchons un traiteur pour un déjeuner buffet. Nous avons quelques contraintes alimentaires (végétariens).',
    'new',
    '2026-02-22 14:15:00'
),
(
    'Marie Dubois',
    'marie.dubois@email.com',
    '+33 6 45 67 89 01',
    30,
    '2026-05-28',
    'Jardin privé - Vignemont',
    'anniversaire',
    'Pour l\'anniversaire de mes 40 ans, je souhaiterais un barbecue convivial dans mon jardin. Les paniers Barbecool et Viandaventure m\'intéressent particulièrement.',
    'in_progress',
    '2026-02-18 09:45:00'
),
(
    'Pierre Lambert',
    'p.lambert@mail.com',
    NULL,
    120,
    '2026-08-20',
    'Domaine des Érables - Compiègne',
    'cocktail',
    'Organisation d\'un cocktail pour l\'inauguration de notre nouvelle boutique. Nous cherchons une prestation haut de gamme avec animation.',
    'quoted',
    '2026-02-15 16:20:00'
);

-- Get the IDs of inserted contacts
SET @contact1 = (SELECT id FROM contact_requests WHERE email = 'sophie.martin@example.com');
SET @contact2 = (SELECT id FROM contact_requests WHERE email = 'jean.dupont@entreprise-abc.fr');
SET @contact3 = (SELECT id FROM contact_requests WHERE email = 'marie.dubois@email.com');

-- Insert menu items for contact 1 (Sophie - Mariage)
INSERT INTO contact_menu_items (contact_id, menu_item_name, menu_item_category, menu_item_price, quantity)
VALUES
(@contact1, 'Plateau apéritif', 'aperitif', 'Sur devis', 1),
(@contact1, 'Buffet froid complet', 'buffet', 'Sur devis', 1);

-- Insert menu items for contact 2 (Jean - Entreprise)
INSERT INTO contact_menu_items (contact_id, menu_item_name, menu_item_category, menu_item_price, quantity)
VALUES
(@contact2, 'Buffet froid complet', 'buffet', 'Sur devis', 1),
(@contact2, 'Formule brunch complète', 'brunch', 'Sur devis', 1);

-- Insert menu items for contact 3 (Marie - Anniversaire)
INSERT INTO contact_menu_items (contact_id, menu_item_name, menu_item_category, menu_item_price, quantity)
VALUES
(@contact3, 'Le Barbecool', 'paniers', '24€', 2),
(@contact3, 'Le Viandaventure', 'paniers', '28€', 1),
(@contact3, 'Saveurs sur braise', 'paniers', '75€', 1);

-- Display results
SELECT '✅ Données de test insérées avec succès !' as Status;

SELECT 
    CONCAT(COUNT(*), ' demandes de contact créées') as Summary
FROM contact_requests;

SELECT 
    CONCAT(COUNT(*), ' items de menu associés') as Summary
FROM contact_menu_items;

-- Display sample data
SELECT 
    cr.id,
    cr.name,
    cr.email,
    cr.type,
    cr.status,
    COUNT(cmi.id) as items_count,
    cr.created_at
FROM contact_requests cr
LEFT JOIN contact_menu_items cmi ON cr.id = cmi.contact_id
GROUP BY cr.id
ORDER BY cr.created_at DESC;

-- ====================================================
-- Dynamic menu catalog sample data
-- ====================================================

INSERT INTO menu_sections (slug, name, description, sort_order, is_active)
VALUES
('paniers', 'Paniers', 'Paniers barbecue et formules conviviales', 10, 1),
('a-la-carte', 'À la carte', 'Viandes et brochettes à la carte', 20, 1),
('aperitif-animation', 'Apéritif & animation', 'Pièces cocktail, format dînatoire et animations culinaires', 30, 1),
('buffet-froid', 'Buffet froid', 'Formules buffet froid pour événements privés et pros', 50, 1),
('plateaux-repas', 'Plateaux repas', 'Plateaux classiques et gourmands, livraison selon zone', 60, 1),
('plat-unique', 'Plat unique & animation poêlon', 'Plats uniques en grand format et animation poêlon', 70, 1),
('brunch', 'Brunch', 'Formules brunch sucré/salé', 40, 1)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    description = VALUES(description),
    sort_order = VALUES(sort_order),
    is_active = VALUES(is_active);

-- Apéritif items
SET @aperitif_section_id = (SELECT id FROM menu_sections WHERE slug = 'aperitif-animation' LIMIT 1);

INSERT INTO menu_items (section_id, slug, name, short_description, image_path, image_alt, price_from_label, sort_order, is_active)
VALUES
(@aperitif_section_id, 'pieces-cocktail', 'Pièces cocktail', 'Formats 5, 7, 9 pièces', '/uploads/pages/menu/aperitif/PiecesCocktail-1200.webp', 'Pièces cocktail', 'À partir de 9,70€', 10, 1),
(@aperitif_section_id, 'format-dinatoire', 'Format dînatoire', 'Formats 12 et 15 pièces', '/uploads/pages/menu/aperitif/FormatDinatoire-1200.webp', 'Format dînatoire', 'À partir de 21€', 20, 1),
(@aperitif_section_id, 'decoupe-jambon', 'Découpe de jambon', 'Animation de découpe', '/uploads/pages/menu/aperitif/DecoupeDeJambon-1200.webp', 'Découpe de jambon', '120€', 30, 1),
(@aperitif_section_id, 'decoupe-saumon', 'Découpe de saumon', 'Gravlax / fumé', '/uploads/pages/menu/aperitif/DecoupeDeSaumon-1200.webp', 'Découpe de saumon', 'Sur devis', 40, 1),
(@aperitif_section_id, 'animation-plancha', 'Animation plancha', 'Animations culinaires à la pièce', '/uploads/pages/menu/aperitif/Plancha-1200.webp', 'Animation plancha', 'Sur devis', 50, 1)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    short_description = VALUES(short_description),
    image_path = VALUES(image_path),
    image_alt = VALUES(image_alt),
    price_from_label = VALUES(price_from_label),
    sort_order = VALUES(sort_order),
    is_active = VALUES(is_active);

-- Plateaux items
SET @plateaux_section_id = (SELECT id FROM menu_sections WHERE slug = 'plateaux-repas' LIMIT 1);

INSERT INTO menu_items (section_id, slug, name, short_description, image_path, image_alt, price_from_label, sort_order, is_active)
VALUES
(@plateaux_section_id, 'plateau-classique', 'Plateau Classique', 'Entrée, plat, salade, dessert', '/uploads/pages/menu/plateau/plateau-classique-1200.webp', 'Plateau repas classique', 'Dès 21,50€', 10, 1),
(@plateaux_section_id, 'plateau-gourmand', 'Plateau Gourmand', 'Entrée, plat, 2 salades, fromages, dessert', '/uploads/pages/menu/plateau/plateau-gourmand-1200.webp', 'Plateau repas gourmand', 'Dès 27€', 20, 1)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    short_description = VALUES(short_description),
    image_path = VALUES(image_path),
    image_alt = VALUES(image_alt),
    price_from_label = VALUES(price_from_label),
    sort_order = VALUES(sort_order),
    is_active = VALUES(is_active);

-- Brunch item
SET @brunch_section_id = (SELECT id FROM menu_sections WHERE slug = 'brunch' LIMIT 1);

INSERT INTO menu_items (section_id, slug, name, short_description, image_path, image_alt, price_from_label, sort_order, is_active)
VALUES
(@brunch_section_id, 'brunch', 'Brunch', 'Formule sucrée/salée avec boissons', '/uploads/pages/menu/brunch/Brunch-1200.webp', 'Brunch', '25€ / personne', 10, 1)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    short_description = VALUES(short_description),
    image_path = VALUES(image_path),
    image_alt = VALUES(image_alt),
    price_from_label = VALUES(price_from_label),
    sort_order = VALUES(sort_order),
    is_active = VALUES(is_active);

-- Paniers items
SET @paniers_section_id = (SELECT id FROM menu_sections WHERE slug = 'paniers' LIMIT 1);

INSERT INTO menu_items (section_id, slug, name, short_description, image_path, image_alt, price_from_label, sort_order, is_active)
VALUES
(@paniers_section_id, 'le-barbecool', 'Le Barbecool', '6 Merguez\n6 Chipolatas au choix\n6 Brochettes de volaille au choix', '/uploads/pages/menu/paniers/Barbecool-1200.webp', 'Le Barbecool', '24€', 10, 1),
(@paniers_section_id, 'le-viandaventure', 'Le Viandaventure', '6 Merguez\n6 Chipolatas au choix\n6 Brochettes de bœuf aux 3 poivres\n6 tranches de poitrine de porc', '/uploads/pages/menu/paniers/Viandaventure-1200.webp', 'Le Viandaventure', '28€', 20, 1),
(@paniers_section_id, 'saveurs-sur-braise', 'Saveurs sur braise', '6 Merguez\n6 Chipolatas au choix\n6 Brochettes de volaille au choix\n6 Brochettes de bœuf aux 3 poivres\n2 magrets de canard (~800g)\n1 travers de porc, sauce barbecue, précuit', '/uploads/pages/menu/paniers/SaveursSurBraise-1200.webp', 'Saveurs sur braise', '75€', 30, 1)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    short_description = VALUES(short_description),
    image_path = VALUES(image_path),
    image_alt = VALUES(image_alt),
    price_from_label = VALUES(price_from_label),
    sort_order = VALUES(sort_order),
    is_active = VALUES(is_active);

-- À la carte items
SET @carte_section_id = (SELECT id FROM menu_sections WHERE slug = 'a-la-carte' LIMIT 1);

INSERT INTO menu_items (section_id, slug, name, short_description, image_path, image_alt, price_from_label, sort_order, is_active)
VALUES
(@carte_section_id, 'merguez', 'Merguez', '', '/uploads/pages/menu/carte/Merguez-1200.webp', 'Merguez', '16,50€/kg', 10, 1),
(@carte_section_id, 'chipolatas', 'Chipolatas', 'Nature\nCurry\nBasquaise\nOrientale\nComté', '/uploads/pages/menu/carte/Chipolatas-1200.webp', 'Chipolatas', '16,50€/kg', 20, 1),
(@carte_section_id, 'lard-barbecue', 'Tranche de poitrine de porc', 'Nature\nTex-mex\nHerbes de Provence', '/uploads/pages/menu/carte/LardBarbecue-1200.webp', 'Tranche de poitrine de porc', '12,50€/kg', 30, 1),
(@carte_section_id, 'travers-de-porc', 'Travers de porc', 'Sauce barbecue', '/uploads/pages/menu/carte/TraversDePorc-1200.webp', 'Travers de porc', '19,50€/kg', 40, 1),
(@carte_section_id, 'travers-porc-marinade', 'Travers de porc marinade', 'Sauce barbecue', '/uploads/pages/menu/carte/TraversDePorcMarinade-1200.webp', 'Travers de porc marinade', '21€/kg', 50, 1),
(@carte_section_id, 'magret-canard', 'Magret de canard', '', '/uploads/pages/menu/carte/MagretDeCanard-1200.webp', 'Magret de canard', '22€/kg', 60, 1),
(@carte_section_id, 'brochettes-volaille', 'Brochettes de volaille', '', '/uploads/pages/menu/carte/BrochettesDeVolaille-1200.webp', 'Brochettes de volaille', '18,50€/kg', 70, 1),
(@carte_section_id, 'brochettes-boeuf', 'Brochettes de bœuf aux 3 poivres', '', '/uploads/pages/menu/carte/BrochettesDeBoeuf-1200.webp', 'Brochettes de bœuf aux 3 poivres', '29,25€/kg', 80, 1)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    short_description = VALUES(short_description),
    image_path = VALUES(image_path),
    image_alt = VALUES(image_alt),
    price_from_label = VALUES(price_from_label),
    sort_order = VALUES(sort_order),
    is_active = VALUES(is_active);

-- Buffet froid items
SET @buffet_section_id = (SELECT id FROM menu_sections WHERE slug = 'buffet-froid' LIMIT 1);

INSERT INTO menu_items (section_id, slug, name, short_description, image_path, image_alt, price_from_label, sort_order, is_active)
VALUES
(@buffet_section_id, 'formule-19', 'Formule 19', '*hors fromages\nCharcuterie, entrée : 1 tranche de terrine, 4 tranches de charcuterie\nViandes : 2 tranches de rôti, 2 tranches de rosbeef, 1 pilon de poulet mariné\nAccompagnement : 4 salades composées au choix, mayonnaise maison, moutarde & cornichons, pain frais, beurre\nFromage (+4,50€) : 3 fromages, salade verte & vinaigrette', '/uploads/pages/menu/buffet/BuffetFroid19-1200.webp', 'Buffet froid formule 19', '19€ / personne*', 10, 1),
(@buffet_section_id, 'formule-24', 'Formule 24', '*hors fromages\nCharcuterie, entrée : 1 tranche de saumon fumé, 1 tranche de pâté-croûte, 4 tranches de charcuterie\nViandes : 2 tranches de rôti, 2 tranches de rosbeef, 1 pilon de poulet mariné\nAccompagnement : 4 salades composées au choix, mayonnaise maison, moutarde & cornichons, pain frais, beurre\nFromage (+4,50€) : 3 fromages, salade verte & vinaigrette', '/uploads/pages/menu/buffet/BuffetFroid24-1200.webp', 'Buffet froid formule 24', '24€ / personne*', 20, 1),
(@buffet_section_id, 'formule-35-50', 'Formule 35,50', '*hors fromages\nCharcuterie, entrée : 1 tranche de saumon fumé, 1 tranche de saumon gravlax, grosses crevettes, 1 opéra de foie gras, 1 tranche de pâté-croûte, 3 tranches de charcuterie\nViandes : carpaccio de rosbeef, magret de canard séché finement tranché\nAccompagnement : 3 salades composées au choix, mayonnaise maison, moutarde & cornichons, pain frais, beurre\nFromage (+4,50€) : 3 fromages, salade verte & vinaigrette', '/uploads/pages/menu/buffet/BuffetFroid35-1200.webp', 'Buffet froid formule 35,50', '35,50€ / personne*', 30, 1)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    short_description = VALUES(short_description),
    image_path = VALUES(image_path),
    image_alt = VALUES(image_alt),
    price_from_label = VALUES(price_from_label),
    sort_order = VALUES(sort_order),
    is_active = VALUES(is_active);

-- Plat unique items
SET @plat_unique_section_id = (SELECT id FROM menu_sections WHERE slug = 'plat-unique' LIMIT 1);

INSERT INTO menu_items (section_id, slug, name, short_description, image_path, image_alt, price_from_label, sort_order, is_active)
VALUES
(@plat_unique_section_id, 'couscous-royal', 'Couscous royal, 4 viandes', '3 viandes - 15€\nEnfants - 10,50€', '/uploads/pages/menu/poelon/Couscous-1200.webp', 'Couscous royal', '16€', 10, 1),
(@plat_unique_section_id, 'moules-frites', 'Moules frites', '', '/uploads/pages/menu/poelon/MoulesFrites-1200.webp', 'Moules frites', '12,50€', 20, 1),
(@plat_unique_section_id, 'tartiflette', 'Tartiflette', 'Supplément salade & jambon cru - 2,50€', '/uploads/pages/menu/poelon/Tartiflette-1200.webp', 'Tartiflette', '11€', 30, 1),
(@plat_unique_section_id, 'paella', 'Paëlla', '', '/uploads/pages/menu/poelon/Paella-1200.webp', 'Paëlla', '12,50€', 40, 1),
(@plat_unique_section_id, 'jambalaya', 'Jambalaya', '', '/uploads/pages/menu/poelon/Jambalaya-1200.webp', 'Jambalaya', '11,50€', 50, 1),
(@plat_unique_section_id, 'rougail-saucisse', 'Rougail saucisse', 'Accompagnement : riz blanc', '/uploads/pages/menu/poelon/RougailSaucisse-1200.webp', 'Rougail saucisse', '12,50€', 60, 1),
(@plat_unique_section_id, 'poelee-campagnarde', 'Poêlée campagnarde', 'Pommes de terre, lardons, oignons, saucisse de Francfort, champignons de Paris', '/uploads/pages/menu/poelon/PoeleeCampagnarde-1200.webp', 'Poêlée campagnarde', '13,50€', 70, 1)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    short_description = VALUES(short_description),
    image_path = VALUES(image_path),
    image_alt = VALUES(image_alt),
    price_from_label = VALUES(price_from_label),
    sort_order = VALUES(sort_order),
    is_active = VALUES(is_active);

-- Options for Pièces cocktail
SET @item_pieces = (
    SELECT mi.id FROM menu_items mi
    INNER JOIN menu_sections ms ON ms.id = mi.section_id
    WHERE ms.slug = 'aperitif-animation' AND mi.slug = 'pieces-cocktail'
    LIMIT 1
);

INSERT INTO menu_item_options (item_id, option_key, label, price_cents, price_label, is_quote_only, sort_order, is_active)
VALUES
(@item_pieces, 'cocktail-5', '5 pièces', 970, '9,70€', 0, 10, 1),
(@item_pieces, 'cocktail-7', '7 pièces', 1350, '13,50€', 0, 20, 1),
(@item_pieces, 'cocktail-9', '9 pièces', 1650, '16,50€', 0, 30, 1)
ON DUPLICATE KEY UPDATE
    label = VALUES(label),
    price_cents = VALUES(price_cents),
    price_label = VALUES(price_label),
    is_quote_only = VALUES(is_quote_only),
    sort_order = VALUES(sort_order),
    is_active = VALUES(is_active);

-- Options for Format dînatoire
SET @item_dinatoire = (
    SELECT mi.id FROM menu_items mi
    INNER JOIN menu_sections ms ON ms.id = mi.section_id
    WHERE ms.slug = 'aperitif-animation' AND mi.slug = 'format-dinatoire'
    LIMIT 1
);

INSERT INTO menu_item_options (item_id, option_key, label, description, price_cents, price_label, is_quote_only, sort_order, is_active)
VALUES
(@item_dinatoire, 'dinatoire-12', '12 pièces', '8 pièces salées, 4 pièces sucrées', 2100, '21€', 0, 10, 1),
(@item_dinatoire, 'dinatoire-15', '15 pièces', '10 pièces salées, 5 pièces sucrées', 2500, '25€', 0, 20, 1)
ON DUPLICATE KEY UPDATE
    label = VALUES(label),
    description = VALUES(description),
    price_cents = VALUES(price_cents),
    price_label = VALUES(price_label),
    is_quote_only = VALUES(is_quote_only),
    sort_order = VALUES(sort_order),
    is_active = VALUES(is_active);

-- Options for Plateau Classique
SET @item_plateau_classique = (
    SELECT mi.id FROM menu_items mi
    INNER JOIN menu_sections ms ON ms.id = mi.section_id
    WHERE ms.slug = 'plateaux-repas' AND mi.slug = 'plateau-classique'
    LIMIT 1
);

INSERT INTO menu_item_options (item_id, option_key, label, price_cents, price_label, is_quote_only, sort_order, is_active)
VALUES
(@item_plateau_classique, 'viande', 'Viande', 2150, '21,50€', 0, 10, 1),
(@item_plateau_classique, 'poisson', 'Poisson', 2350, '23,50€', 0, 20, 1),
(@item_plateau_classique, 'vegetarien', 'Végétarien', 2150, '21,50€', 0, 30, 1)
ON DUPLICATE KEY UPDATE
    label = VALUES(label),
    price_cents = VALUES(price_cents),
    price_label = VALUES(price_label),
    is_quote_only = VALUES(is_quote_only),
    sort_order = VALUES(sort_order),
    is_active = VALUES(is_active);

-- Options for Plateau Gourmand
SET @item_plateau_gourmand = (
    SELECT mi.id FROM menu_items mi
    INNER JOIN menu_sections ms ON ms.id = mi.section_id
    WHERE ms.slug = 'plateaux-repas' AND mi.slug = 'plateau-gourmand'
    LIMIT 1
);

INSERT INTO menu_item_options (item_id, option_key, label, price_cents, price_label, is_quote_only, sort_order, is_active)
VALUES
(@item_plateau_gourmand, 'viande', 'Viande', 2700, '27€', 0, 10, 1),
(@item_plateau_gourmand, 'poisson', 'Poisson', 2900, '29€', 0, 20, 1),
(@item_plateau_gourmand, 'vegetarien', 'Végétarien', 2700, '27€', 0, 30, 1)
ON DUPLICATE KEY UPDATE
    label = VALUES(label),
    price_cents = VALUES(price_cents),
    price_label = VALUES(price_label),
    is_quote_only = VALUES(is_quote_only),
    sort_order = VALUES(sort_order),
    is_active = VALUES(is_active);
