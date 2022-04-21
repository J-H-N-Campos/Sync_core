# FORCE PERMISSÕES DO DIRETORIO
# author: Rodrigo de Freitas

#000 = --- = 0 = nenhuma permissão
#001 = --x = 1 = somente permissão de execução
#010 = -w- = 2 = somente permissão de escrita
#011 = -wx = 3 = somente permissões de escrita e execução
#100 = r-- = 4 = somente permissão de leitura
#101 = r-x = 5 = somente permissões de leitura e execução
#110 = rw- = 6 = somente permissões de leitura e escrita
#111 = rwx = 7 = permissões de leitura, escrita e execução (acesso total)

#ARQUIVOS NORMAIS dono/grupo/outros
chmod 755 ../ -Rf;

#ARQUIVOS MODIFICAVEIS dono/grupo/outros
chmod 773 ../tmp/ ../repository/ -Rf;

#ARQUIVOS NORMAIS dono/grupo/outros
chmod 776 ../.htaccess ../app/config/ ../hosts/ -Rf;

#dono:grupo
chown root.www-data ../ -Rf;
chown www-data ../ -Rf;

