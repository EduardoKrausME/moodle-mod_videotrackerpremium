<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * videotrackerpremium.php
 *
 * @package   mod_videotrackerpremium
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$string['activity_completed_late'] = 'Atividade concluída com atraso';
$string['adminnote'] = 'Observação administrativa';
$string['all'] = 'Todos';
$string['allowlate'] = 'Permitir visualização após prazo e tolerância';
$string['applyselected'] = 'Aplicar aos alunos selecionados';
$string['availablefrom'] = 'Disponível a partir de';
$string['bulkaction'] = 'Ação em lote';
$string['bulkcompleted'] = 'A ação foi aplicada a {$a} aluno(s).';
$string['bulkconfirm'] = 'Confirme a ação em lote selecionada';
$string['bulkconfirmcount'] = 'Esta ação afetará {$a} aluno(s).';
$string['bulkqueued'] = 'A ação para {$a} aluno(s) foi enviada para processamento em segundo plano.';
$string['completed'] = 'Concluídos';
$string['completedlate'] = 'Concluído atrasado';
$string['completedontime'] = 'Concluído dentro do prazo';
$string['completion'] = 'Conclusão';
$string['completiondetail'] = 'Assistir a pelo menos {$a}% do vídeo.';
$string['completionrequired'] = 'Exigir o percentual mínimo assistido para conclusão';
$string['complianceheader'] = 'Prazos e compliance';
$string['continuewatching'] = 'Continuar assistindo';
$string['currentpercent'] = 'Progresso atual';
$string['currentstatussummary'] = 'Progresso atual: {$a->percent}%. Status operacional: {$a->status}.';
$string['dashboard'] = 'Dashboard operacional';
$string['days'] = 'Dias';
$string['deadline'] = 'Prazo';
$string['deadline_extended'] = 'Prazo estendido';
$string['deadlineafteropen'] = 'O prazo deve ser posterior à data inicial.';
$string['deadlineextendedcolumn'] = 'Prazo estendido';
$string['deadlinefrom'] = 'Prazo a partir de';
$string['deadlineto'] = 'Prazo até';
$string['deadlinevalue'] = 'Prazo: {$a}';
$string['details'] = 'Detalhes';
$string['duesoon'] = 'Vencendo';
$string['everyday'] = 'Todos os dias';
$string['everyxdays'] = 'A cada {$a} dias';
$string['exportcsv'] = 'Exportar CSV';
$string['exportcsvwithhistory'] = 'Exportar CSV com histórico administrativo';
$string['extenddeadline'] = 'Estender prazo';
$string['extended'] = 'Prazo estendido';
$string['filterstatus'] = 'Status';
$string['graceperiod'] = 'Período de tolerância';
$string['history'] = 'Histórico administrativo';
$string['historydeadline'] = 'Prazo alterado de {$a->old} para {$a->new}.';
$string['inprogress'] = 'Em andamento';
$string['invalidpercent'] = 'Informe um percentual entre 1 e 100.';
$string['invalidrepeatinterval'] = 'O intervalo de repetição deve estar entre 0 e 3650 dias.';
$string['lastaccess'] = 'Último acesso';
$string['lastreminder'] = 'Último lembrete';
$string['lastsession'] = 'Última sessão';
$string['lateviewingblocked'] = 'O período de visualização terminou. Procure a equipe do curso caso precise de uma extensão.';
$string['maximumpercentfilter'] = 'Percentual máximo';
$string['messageavailabledefault'] = 'Olá {firstname}, {activityname} já está disponível em {course}. Prazo: {deadline}.';
$string['messagecompleted_default'] = 'Olá {firstname}, sua conclusão de {activityname} foi confirmada em {percent}%.';
$string['messageheader'] = 'Templates de mensagens';
$string['messagenear_default'] = 'Olá {firstname}, o prazo de {activityname} está próximo ({deadline}). Seu progresso atual é {percent}%.';
$string['messageoverdue_default'] = 'Olá {firstname}, {activityname} está atrasado. Prazo: {deadline}. Seu progresso atual é {percent}%.';
$string['messageplaceholders'] = 'Placeholders das mensagens';
$string['messageplaceholders_help'] = 'Placeholders permitidos: {firstname}, {activityname}, {deadline}, {percent}, {course}. Não há suporte a PHP ou expressões avaliadas.';
$string['messageprovider:reminders'] = 'Lembretes e notificações de compliance';
$string['messagesubject_available'] = '{$a}: disponível';
$string['messagesubject_completed'] = '{$a}: conclusão confirmada';
$string['messagesubject_manual'] = '{$a}: lembrete';
$string['messagesubject_near'] = '{$a}: prazo próximo';
$string['messagesubject_overdue'] = '{$a}: atrasado';
$string['messagesubject_tomorrow'] = '{$a}: prazo amanhã';
$string['messagetomorrow_default'] = 'Olá {firstname}, {activityname} vence amanhã ({deadline}). Seu progresso atual é {percent}%.';
$string['minimumpercent'] = 'Percentual mínimo assistido';
$string['minimumpercent_help'] = 'O progresso autoritativo do Video Bridge precisa atingir este percentual para conclusão.';
$string['minimumpercentfilter'] = 'Percentual mínimo';
$string['modulename'] = 'Video Tracker Premium';
$string['modulenameplural'] = 'Atividades Video Tracker Premium';
$string['no'] = 'Não';
$string['noactivities'] = 'Não há atividades Video Tracker Premium neste curso.';
$string['nodeadline'] = 'Sem prazo';
$string['norows'] = 'Nenhum aluno corresponde aos filtros selecionados.';
$string['notavailableyet'] = 'Este vídeo ainda não está disponível.';
$string['notcompleted'] = 'Não concluído';
$string['notrackingsources'] = 'Nenhuma fonte do Video Bridge com tracking confiável está instalada.';
$string['notstarted'] = 'Não iniciados';
$string['overdue'] = 'Atrasados';
$string['overduefriendly'] = 'Atrasado há {$a}.';
$string['override'] = 'Exceção';
$string['overridefor'] = 'Exceção para {$a}';
$string['overridesaved'] = 'A exceção individual foi salva.';
$string['percent'] = 'Percentual';
$string['playererror'] = 'Não foi possível inicializar o player. Recarregue a página ou procure a equipe do curso se o problema continuar.';
$string['pluginadministration'] = 'Administração do Video Tracker Premium';
$string['pluginname'] = 'Video Tracker Premium';
$string['privacy:metadata:history'] = 'Histórico de alterações administrativas relacionadas ao aluno.';
$string['privacy:metadata:notify'] = 'Agendamento e log de envio de lembretes.';
$string['privacy:metadata:override'] = 'Exceções individuais de prazo, dispensa e observações administrativas.';
$string['privacy:metadata:state'] = 'Estado operacional de conclusão derivado do progresso do Video Bridge.';
$string['receivedreminder'] = 'Recebeu lembrete';
$string['remainingfriendly'] = 'Faltam {$a}.';
$string['reminder1'] = 'Lembrar 1 dia antes';
$string['reminder3'] = 'Lembrar 3 dias antes';
$string['reminder7'] = 'Lembrar 7 dias antes';
$string['reminder_sent'] = 'Lembrete enviado';
$string['reminderafter1'] = 'Lembrar 1 dia depois';
$string['reminderday'] = 'Lembrar no dia do prazo';
$string['reminderheader'] = 'Lembretes';
$string['remindersenabled'] = 'Ativar lembretes';
$string['removewaiver'] = 'Remover dispensa';
$string['repeatlateevery'] = 'Repetir enquanto estiver atrasado';
$string['report'] = 'Relatório de compliance';
$string['requiredpercent'] = 'Progresso necessário';
$string['sendavailable'] = 'Avisar quando a atividade ficar disponível';
$string['sendremindernow'] = 'Enviar lembrete agora';
$string['status'] = 'Status';
$string['status_completed'] = 'Concluído';
$string['status_duesoon'] = 'Vence em breve';
$string['status_duetoday'] = 'Vence hoje';
$string['status_extended'] = 'Prazo estendido';
$string['status_inprogress'] = 'Em andamento';
$string['status_notavailable'] = 'Não disponível';
$string['status_notstarted'] = 'Não iniciado';
$string['status_overdue'] = 'Atrasado';
$string['status_waived'] = 'Dispensado';
$string['student'] = 'Aluno';
$string['taskprocessreminders'] = 'Processar lembretes do Video Tracker Premium';
$string['templateavailable'] = 'Atividade disponível';
$string['templatecompleted'] = 'Conclusão confirmada';
$string['templatenear'] = 'Prazo próximo';
$string['templateoverdue'] = 'Atrasado';
$string['templatetomorrow'] = 'Prazo amanhã';
$string['timeremaining'] = 'Tempo restante';
$string['total'] = 'Total';
$string['user_waived'] = 'Aluno dispensado';
$string['videoheader'] = 'Vídeo';
$string['videosource'] = 'Fonte do vídeo';
$string['videotrackerpremium:addinstance'] = 'Adicionar atividade Video Tracker Premium';
$string['videotrackerpremium:export'] = 'Exportar relatórios de compliance';
$string['videotrackerpremium:manageoverrides'] = 'Gerenciar exceções individuais';
$string['videotrackerpremium:sendreminders'] = 'Enviar lembretes';
$string['videotrackerpremium:view'] = 'Visualizar Video Tracker Premium';
$string['videotrackerpremium:viewadminhistory'] = 'Visualizar histórico administrativo';
$string['videotrackerpremium:viewreport'] = 'Visualizar dashboard operacional';
$string['videotrackerpremiumname'] = 'Nome da atividade';
$string['waive'] = 'Dispensar';
$string['waived'] = 'Dispensados';
$string['waiver_removed'] = 'Dispensa removida';
$string['yes'] = 'Sim';
