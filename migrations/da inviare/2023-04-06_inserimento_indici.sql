ALTER TABLE `or_righe_ordini`
ADD INDEX `idarticolo` (`idarticolo`),
ADD INDEX `idordine` (`idordine`),
ADD INDEX `qta` (`qta`),
ADD INDEX `qta_evasa` (`qta_evasa`),
ADD INDEX `confermato` (`confermato`);

ALTER TABLE `mg_articoli_sedi`
ADD INDEX `id_articolo` (`id_articolo`),
ADD INDEX `id_sede` (`id_sede`),
ADD INDEX `id_anagrafica` (`id_anagrafica`);

ALTER TABLE `or_ordini`
ADD INDEX `id_sede_partenza` (`id_sede_partenza`),
ADD INDEX `idtipoordine` (`idtipoordine`),
ADD INDEX `idanagrafica` (`idanagrafica`),
ADD INDEX `idstatoordine` (`idstatoordine`);
