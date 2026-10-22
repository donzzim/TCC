import MaterialIcons from '@expo/vector-icons/MaterialIcons';
import {
  CameraView,
  useCameraPermissions,
  type BarcodeScanningResult,
  type BarcodeType,
} from 'expo-camera';
import { router } from 'expo-router';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  Animated,
  Easing,
  FlatList,
  Modal,
  Pressable,
  RefreshControl,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { AppMenuButton } from '@/components/app-menu-button';
import { BottomBar } from '@/components/bottom-bar';
import { authApi, type MobileUser } from '@/src/api/auth';
import { ApiError, NetworkError } from '@/src/api/errors';
import {
  conferenciaApi,
  type BemConferencia,
  type ConferenciaBensResult,
  type ConferenciaInfo,
  type ConferenciaStatus,
  type ResultadoLeitura,
} from '@/src/api/conferencia';
import { useThemeStyles } from '@/src/theme/useThemeStyles';

const CONFERENCIA_BENS_PER_PAGE = 30;

const BARCODE_TYPES: BarcodeType[] = [
  'ean13',
  'ean8',
  'upc_a',
  'upc_e',
  'code39',
  'code93',
  'code128',
  'codabar',
  'itf14',
];

type FilterStatus = 'todos' | ConferenciaStatus;
type NotificationTone = 'success' | 'error' | 'info';

interface NotificationState {
  message: string;
  tone: NotificationTone;
}

interface DivergenciaTarget {
  bem: BemConferencia | null;
  codigo: string;
  originatedFromRead: boolean;
}

const FILTERS: { key: FilterStatus; label: string }[] = [
  { key: 'todos', label: 'Todos' },
  { key: 'pendente', label: 'Pendentes' },
  { key: 'localizado', label: 'Localizados' },
  { key: 'nao_localizado', label: 'Não localizados' },
  { key: 'divergente', label: 'Divergentes' },
  { key: 'em_transferencia', label: 'Transferência' },
  { key: 'cadastrado_manualmente', label: 'Manuais' },
];

function onlyDigits(value: string): string {
  return value.replace(/\D/g, '');
}

function displayValue(value: unknown, fallback = '-'): string {
  if (value === null || value === undefined || value === '') {
    return fallback;
  }

  return String(value);
}

function getRequestErrorMessage(error: unknown): string {
  if (error instanceof ApiError || error instanceof NetworkError) {
    return error.message;
  }

  return 'Não foi possível concluir a operação.';
}

function getBemCodigo(bem: BemConferencia): string {
  return displayValue(bem.patrimonio ?? bem.codigo ?? bem.codigo_patrimonial ?? bem.id, 'Sem código');
}

function getBemDescricao(bem: BemConferencia): string {
  return displayValue(bem.descricao_resumida ?? bem.descricao ?? bem.denominacao, 'Bem patrimonial');
}

function getReferenciaNome(value: BemConferencia['setor']): string {
  if (typeof value === 'object' && value !== null && 'nome' in value) {
    return displayValue(value.nome);
  }

  return displayValue(value);
}

function getStatus(bem: BemConferencia): ConferenciaStatus {
  return bem.conferencia?.status ?? 'pendente';
}

function getStatusLabel(bem: BemConferencia): string {
  return bem.conferencia?.status_label ?? 'Pendente';
}

export default function ConferenciaScreen() {
  const themed = useThemeStyles();
  const [user, setUser] = useState<MobileUser | null>(null);
  const [info, setInfo] = useState<ConferenciaInfo | null>(null);
  const [bens, setBens] = useState<BemConferencia[]>([]);
  const [totalBensListados, setTotalBensListados] = useState(0);
  const [currentPage, setCurrentPage] = useState(1);
  const [hasMore, setHasMore] = useState(false);
  const [isLoading, setIsLoading] = useState(true);
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [isLoadingMore, setIsLoadingMore] = useState(false);
  const [isLoadingList, setIsLoadingList] = useState(false);
  const [manualCode, setManualCode] = useState('');
  const [resultadoLeitura, setResultadoLeitura] = useState<ResultadoLeitura | null>(null);
  const [notification, setNotification] = useState<NotificationState | null>(null);
  const toastOpacity = useRef(new Animated.Value(0)).current;
  const toastTranslateY = useRef(new Animated.Value(24)).current;
  const [filterStatus, setFilterStatus] = useState<FilterStatus>('pendente');
  const [cameraPermission, requestCameraPermission] = useCameraPermissions();
  const [isScannerActive, setIsScannerActive] = useState(false);
  const [hasScannedBarcode, setHasScannedBarcode] = useState(false);
  const scannerLockedRef = useRef(false);
  const [isValidating, setIsValidating] = useState(false);
  const validationLockedRef = useRef(false);
  const [actionLoading, setActionLoading] = useState<string | null>(null);
  const [selectedBem, setSelectedBem] = useState<BemConferencia | null>(null);
  const [naoLocalizadoBem, setNaoLocalizadoBem] = useState<BemConferencia | null>(null);
  const [divergenciaTarget, setDivergenciaTarget] = useState<DivergenciaTarget | null>(null);
  const [justificativa, setJustificativa] = useState('');
  const [camposDivergentes, setCamposDivergentes] = useState('');
  const [observacaoDivergencia, setObservacaoDivergencia] = useState('');

  const getThemedStatusColor = useCallback((status: ConferenciaStatus): string => {
    switch (status) {
      case 'localizado':
        return themed.colors.success;
      case 'pendente':
      case 'cadastrado_manualmente':
        return themed.colors.warning;
      case 'nao_localizado':
        return themed.colors.danger;
      case 'divergente':
      case 'em_transferencia':
        return themed.colors.info;
      default:
        return themed.colors.textMuted;
    }
  }, [themed.colors]);

  const loadConferencia = useCallback(async ({
    pageToLoad = 1,
    append = false,
    refreshing = false,
    status = filterStatus,
    showLoader = true,
  }: {
    pageToLoad?: number;
    append?: boolean;
    refreshing?: boolean;
    status?: FilterStatus;
    showLoader?: boolean;
  } = {}) => {
    if (append) {
      setIsLoadingMore(true);
    } else if (refreshing) {
      setIsRefreshing(true);
    } else if (showLoader) {
      setIsLoading(true);
    } else {
      setIsLoadingList(true);
    }

    try {
      const session = await authApi.getStoredSession();

      if (!session) {
        router.replace('/');
        return;
      }

      setUser(session.user);
      const data: ConferenciaBensResult = await conferenciaApi.listarBens({
        page: pageToLoad,
        perPage: CONFERENCIA_BENS_PER_PAGE,
        status,
      });
      setInfo({
        inventario: data.inventario,
        atividade: data.atividade,
        resumo: data.resumo,
        scope: data.scope,
      });
      setBens((currentBens) => append ? [...currentBens, ...data.bens] : data.bens);
      setTotalBensListados(data.total);
      setCurrentPage(data.meta.current_page);
      setHasMore(data.meta.has_more);
    } catch (error) {
      if (append) {
        setHasMore(false);
      }

      setNotification({ message: getRequestErrorMessage(error), tone: 'error' });
    } finally {
      setIsLoading(false);
      setIsRefreshing(false);
      setIsLoadingMore(false);
      setIsLoadingList(false);
    }
  }, [filterStatus]);

  useEffect(() => {
    loadConferencia();
  }, [loadConferencia]);

  useEffect(() => {
    if (!notification) {
      toastOpacity.setValue(0);
      toastTranslateY.setValue(24);
      return;
    }

    toastOpacity.stopAnimation();
    toastTranslateY.stopAnimation();
    toastOpacity.setValue(0);
    toastTranslateY.setValue(24);

    Animated.parallel([
      Animated.timing(toastOpacity, {
        toValue: 1,
        duration: 220,
        easing: Easing.out(Easing.cubic),
        useNativeDriver: true,
      }),
      Animated.timing(toastTranslateY, {
        toValue: 0,
        duration: 220,
        easing: Easing.out(Easing.cubic),
        useNativeDriver: true,
      }),
    ]).start();

    const dismissTimeout = setTimeout(() => {
      Animated.parallel([
        Animated.timing(toastOpacity, {
          toValue: 0,
          duration: 180,
          easing: Easing.in(Easing.cubic),
          useNativeDriver: true,
        }),
        Animated.timing(toastTranslateY, {
          toValue: 18,
          duration: 180,
          easing: Easing.in(Easing.cubic),
          useNativeDriver: true,
        }),
      ]).start(({ finished }) => {
        if (finished) {
          setNotification((currentNotification) => (
            currentNotification === notification ? null : currentNotification
          ));
        }
      });
    }, 3600);

    return () => {
      clearTimeout(dismissTimeout);
    };
  }, [notification, toastOpacity, toastTranslateY]);

  const filteredBens = useMemo(() => {
    if (filterStatus === 'todos') {
      return bens;
    }

    return bens.filter((bem) => getStatus(bem) === filterStatus);
  }, [bens, filterStatus]);

  const lastReadBem = resultadoLeitura?.bem ?? null;
  const canEdit = info?.atividade.pode_editar ?? false;

  function showNotification(message: string, tone: NotificationTone) {
    setNotification({ message, tone });
  }

  function mergeBens(updatedBens: BemConferencia[]) {
    if (updatedBens.length === 0) {
      return;
    }

    setBens((currentBens) => {
      const updatedById = new Map(updatedBens.map((bem) => [String(bem.id), bem]));

      return currentBens
        .map((bem) => updatedById.get(String(bem.id)) ?? bem)
        .filter((bem) => filterStatus === 'todos' || getStatus(bem) === filterStatus);
    });
  }

  function applyActionResult(result: {
    message?: string;
    bem?: BemConferencia | null;
    bens?: BemConferencia[];
    resumo?: ConferenciaInfo['resumo'];
    inventario?: ConferenciaInfo['inventario'];
    atividade?: ConferenciaInfo['atividade'];
  }) {
    setInfo((currentInfo) => {
      if (!currentInfo) {
        return currentInfo;
      }

      return {
        inventario: result.inventario ?? currentInfo.inventario,
        atividade: result.atividade ?? currentInfo.atividade,
        resumo: result.resumo ?? currentInfo.resumo,
        scope: currentInfo.scope,
      };
    });

    const updatedBens = [
      ...(result.bem ? [result.bem] : []),
      ...(result.bens ?? []),
    ];

    if (filterStatus !== 'todos') {
      const removedFromCurrentFilter = updatedBens.filter((bem) => getStatus(bem) !== filterStatus).length;
      setTotalBensListados((currentTotal) => Math.max(0, currentTotal - removedFromCurrentFilter));

      if (removedFromCurrentFilter > 0) {
        loadConferencia({
          pageToLoad: 1,
          status: filterStatus,
          showLoader: false,
        });
      }
    }

    mergeBens(updatedBens);

    if (result.message) {
      showNotification(result.message, 'success');
    }
  }

  async function handleRefresh() {
    await loadConferencia({
      pageToLoad: 1,
      refreshing: true,
      status: filterStatus,
      showLoader: false,
    });
  }

  function handleLoadMore() {
    if (isLoading || isRefreshing || isLoadingMore || isLoadingList || !hasMore) {
      return;
    }

    loadConferencia({
      pageToLoad: currentPage + 1,
      append: true,
      status: filterStatus,
      showLoader: false,
    });
  }

  async function handleStartScanner() {
    setNotification(null);

    if (isScannerActive) {
      scannerLockedRef.current = true;
      setIsScannerActive(false);
      return;
    }

    if (!cameraPermission?.granted) {
      const permission = await requestCameraPermission();

      if (!permission.granted) {
        showNotification('Permita o acesso à câmera para ler o patrimônio.', 'error');
        return;
      }
    }

    scannerLockedRef.current = false;
    setHasScannedBarcode(false);
    setIsScannerActive(true);
  }

  async function validateCode(code: string) {
    if (validationLockedRef.current) {
      return;
    }

    const trimmedCode = onlyDigits(code);

    if (!trimmedCode) {
      showNotification('Informe ou leia um patrimônio.', 'error');
      return;
    }

    validationLockedRef.current = true;
    setIsValidating(true);
    setResultadoLeitura(null);

    try {
      const result = await conferenciaApi.validarLeitura(trimmedCode);

      setResultadoLeitura(result);
      setManualCode(trimmedCode);
      showNotification(result.message, result.pode_localizar ? 'success' : 'info');
    } catch (error) {
      showNotification(getRequestErrorMessage(error), 'error');
    } finally {
      validationLockedRef.current = false;
      setIsValidating(false);
    }
  }

  function handleBarcodeScanned(result: BarcodeScanningResult) {
    if (scannerLockedRef.current) {
      return;
    }

    scannerLockedRef.current = true;
    setHasScannedBarcode(true);
    setIsScannerActive(false);
    validateCode(result.data);
  }

  async function handleLocalizar(bem?: BemConferencia | null) {
    const targetBem = bem ?? lastReadBem;
    const payload = targetBem?.id ? { bem_id: targetBem.id } : { codigo: manualCode };

    setActionLoading('localizar');

    try {
      const result = await conferenciaApi.localizar(payload);
      setResultadoLeitura(null);
      setManualCode('');
      applyActionResult(result);
    } catch (error) {
      showNotification(getRequestErrorMessage(error), 'error');
    } finally {
      setActionLoading(null);
    }
  }

  async function handleRegistrarNaoLocalizado() {
    if (!naoLocalizadoBem) {
      return;
    }

    if (!justificativa.trim()) {
      showNotification('Justificativa obrigatória.', 'error');
      return;
    }

    setActionLoading('nao-localizado');

    try {
      const result = await conferenciaApi.registrarNaoLocalizado([naoLocalizadoBem.id], justificativa.trim());
      setNaoLocalizadoBem(null);
      setJustificativa('');
      applyActionResult(result);
    } catch (error) {
      showNotification(getRequestErrorMessage(error), 'error');
    } finally {
      setActionLoading(null);
    }
  }

  function openDivergencia(
    bem: BemConferencia | null,
    codigo = '',
    originatedFromRead = false,
    campos = '',
    observacao = '',
  ) {
    setDivergenciaTarget({
      bem,
      codigo: codigo || (bem ? getBemCodigo(bem) : manualCode),
      originatedFromRead,
    });
    setCamposDivergentes(campos);
    setObservacaoDivergencia(observacao);
  }

  function closeDivergencia() {
    setDivergenciaTarget(null);
    setCamposDivergentes('');
    setObservacaoDivergencia('');
  }

  function handleDivergenciaLeitura() {
    if (!resultadoLeitura) {
      return;
    }

    if (resultadoLeitura.status === 'outro_setor') {
      openDivergencia(
        lastReadBem,
        manualCode,
        true,
        'Setor',
        'Bem encontrado fisicamente neste setor, mas cadastrado em outro setor.',
      );
      return;
    }

    if (resultadoLeitura.status === 'nao_cadastrado') {
      openDivergencia(
        null,
        manualCode,
        true,
        'Cadastro patrimonial',
        'Bem encontrado fisicamente neste setor, mas nao consta no cadastro patrimonial digital.',
      );
      return;
    }

    openDivergencia(lastReadBem, manualCode, true);
  }

  async function handleRegistrarDivergencia() {
    if (!divergenciaTarget) {
      return;
    }

    if (!observacaoDivergencia.trim()) {
      showNotification('Observação obrigatória.', 'error');
      return;
    }

    const campos = camposDivergentes
      .split(',')
      .map((campo) => campo.trim())
      .filter(Boolean);

    setActionLoading('divergencia');

    try {
      const result = await conferenciaApi.registrarDivergencia({
        bem_id: divergenciaTarget.bem?.id,
        codigo: divergenciaTarget.bem ? undefined : divergenciaTarget.codigo,
        campos,
        observacao: observacaoDivergencia.trim(),
      });

      if (divergenciaTarget.originatedFromRead) {
        setResultadoLeitura(null);
        setManualCode('');
      }

      closeDivergencia();
      applyActionResult(result);
    } catch (error) {
      showNotification(getRequestErrorMessage(error), 'error');
    } finally {
      setActionLoading(null);
    }
  }

  function handleFinalizar() {
    Alert.alert(
      'Finalizar conferência',
      'A atividade do setor será bloqueada para novas alterações.',
      [
        { text: 'Cancelar', style: 'cancel' },
        {
          text: 'Finalizar',
          style: 'destructive',
          onPress: async () => {
            setActionLoading('finalizar');

            try {
              const result = await conferenciaApi.finalizar();
              applyActionResult(result);
            } catch (error) {
              showNotification(getRequestErrorMessage(error), 'error');
            } finally {
              setActionLoading(null);
            }
          },
        },
      ],
    );
  }

  const renderMetric = (label: string, value: number, color = themed.colors.primary) => (
    <View style={[styles.metricItem, themed.surface]} key={label}>
      <Text style={[styles.metricValue, { color }]}>{value}</Text>
      <Text style={[styles.metricLabel, themed.mutedText]}>{label}</Text>
    </View>
  );

  const renderToast = (isModalToast = false) => notification ? (
    <Animated.View
      pointerEvents="none"
      style={[
        styles.toast,
        isModalToast ? styles.modalToast : styles.screenToast,
        {
          backgroundColor: notification.tone === 'success'
            ? themed.colors.success
            : notification.tone === 'error'
              ? themed.colors.danger
              : themed.colors.info,
        },
        {
          opacity: toastOpacity,
          transform: [{ translateY: toastTranslateY }],
        },
      ]}>
      <MaterialIcons
        name={notification.tone === 'success' ? 'check-circle' : notification.tone === 'error' ? 'error-outline' : 'info-outline'}
        size={20}
        color={themed.colors.primaryText}
      />
      <Text style={[styles.toastText, themed.onPrimaryText]}>{notification.message}</Text>
    </Animated.View>
  ) : null;

  const renderBemRow = (bem: BemConferencia) => {
    const status = getStatus(bem);
    const statusColor = getThemedStatusColor(status);
    const canLocalizarBem = canEdit && ['pendente', 'cadastrado_manualmente'].includes(status);
    const canNaoLocalizadoBem = canEdit && status === 'pendente';

    return (
      <Pressable
        key={`${bem.id}-${getBemCodigo(bem)}`}
        onPress={() => setSelectedBem(bem)}
        style={({ pressed }) => [
          styles.assetRow,
          themed.mutedSurface,
          pressed && styles.pressed,
        ]}>
        <View style={[styles.assetIcon, themed.primarySurface]}>
          <MaterialIcons name="inventory-2" size={20} color={themed.colors.primary} />
        </View>

        <View style={styles.assetInfo}>
          <View style={styles.assetTitleRow}>
            <Text style={[styles.assetCode, themed.text]}>{getBemCodigo(bem)}</Text>
            <View style={[styles.statusBadge, { backgroundColor: `${statusColor}18` }]}>
              <Text style={[styles.statusBadgeText, { color: statusColor }]} numberOfLines={1}>
                {getStatusLabel(bem)}
              </Text>
            </View>
          </View>
          <Text style={[styles.assetDescription, themed.mutedText]} numberOfLines={2}>{getBemDescricao(bem)}</Text>
          <Text style={[styles.assetMeta, themed.subtleText]} numberOfLines={1}>
            {displayValue(bem.marca)} | {displayValue(bem.modelo)}
          </Text>

          {canEdit ? (
            <View style={[styles.inlineActions, { borderTopColor: themed.colors.border }]}>
              {canLocalizarBem ? (
                <Pressable
                  onPress={() => handleLocalizar(bem)}
                  style={[styles.inlineActionPrimary, { backgroundColor: themed.colors.success }]}>
                  <MaterialIcons name="check" size={17} color={themed.colors.primaryText} />
                  <Text style={[styles.inlineActionPrimaryText, themed.onPrimaryText]}>Localizar</Text>
                </Pressable>
              ) : null}
              {canNaoLocalizadoBem ? (
                <Pressable
                  onPress={() => setNaoLocalizadoBem(bem)}
                  style={[styles.inlineActionSecondary, themed.dangerSurface]}>
                  <MaterialIcons name="block" size={17} color={themed.colors.danger} />
                  <Text style={[styles.inlineActionDangerText, { color: themed.colors.danger }]}>Não localizado</Text>
                </Pressable>
              ) : null}
              <Pressable onPress={() => openDivergencia(bem)} style={[styles.inlineActionSecondary, themed.primarySurface]}>
                <MaterialIcons name="report-problem" size={17} color={themed.colors.primary} />
                <Text style={[styles.inlineActionSecondaryText, themed.primaryText]}>Divergência</Text>
              </Pressable>
            </View>
          ) : null}
        </View>
      </Pressable>
    );
  };

  if (isLoading) {
    return (
      <SafeAreaView style={[styles.safeArea, themed.screen]}>
        <View style={styles.loadingContainer}>
          <ActivityIndicator color={themed.colors.primary} />
          <Text style={[styles.loadingText, themed.mutedText]}>Carregando conferência</Text>
        </View>
      </SafeAreaView>
    );
  }

  return (
    <SafeAreaView style={[styles.safeArea, themed.screen]}>
      <Modal transparent animationType="fade" visible={selectedBem !== null} onRequestClose={() => setSelectedBem(null)}>
        <View style={[styles.modalOverlay, themed.overlay]}>
          <View style={[styles.modalPanel, { backgroundColor: themed.colors.surface }]}>
            <View style={[styles.modalHeader, { borderBottomColor: themed.colors.border }]}>
              <View style={styles.modalHeaderText}>
                <Text style={[styles.modalEyebrow, themed.mutedText]}>Patrimônio</Text>
                <Text style={[styles.modalTitle, themed.text]}>{selectedBem ? getBemCodigo(selectedBem) : '-'}</Text>
              </View>
              <Pressable onPress={() => setSelectedBem(null)} style={[styles.iconButton, themed.primarySurface]}>
                <MaterialIcons name="close" size={22} color={themed.colors.primary} />
              </Pressable>
            </View>
            {selectedBem ? (
              <ScrollView contentContainerStyle={styles.modalContent}>
                <Text style={[styles.modalDescription, themed.mutedText]}>{getBemDescricao(selectedBem)}</Text>
                <View style={[styles.detailGrid, themed.mutedSurface]}>
                  <Text style={[styles.detailLabel, themed.mutedText]}>Status</Text>
                  <Text style={[styles.detailValue, themed.text]}>{getStatusLabel(selectedBem)}</Text>
                  <Text style={[styles.detailLabel, themed.mutedText]}>Setor</Text>
                  <Text style={[styles.detailValue, themed.text]}>{getReferenciaNome(selectedBem.setor)}</Text>
                  <Text style={[styles.detailLabel, themed.mutedText]}>Complemento</Text>
                  <Text style={[styles.detailValue, themed.text]}>{getReferenciaNome(selectedBem.complemento_setor)}</Text>
                  <Text style={[styles.detailLabel, themed.mutedText]}>Série</Text>
                  <Text style={[styles.detailValue, themed.text]}>{displayValue(selectedBem.numero_serie)}</Text>
                  <Text style={[styles.detailLabel, themed.mutedText]}>Conservação</Text>
                  <Text style={[styles.detailValue, themed.text]}>{displayValue(selectedBem.estado_conservacao)}</Text>
                  <Text style={[styles.detailLabel, themed.mutedText]}>Observação</Text>
                  <Text style={[styles.detailValue, themed.text]}>{displayValue(selectedBem.conferencia?.observacao_item ?? selectedBem.observacao)}</Text>
                </View>
              </ScrollView>
            ) : null}
          </View>
          {renderToast(true)}
        </View>
      </Modal>

      <Modal transparent animationType="fade" visible={naoLocalizadoBem !== null} onRequestClose={() => setNaoLocalizadoBem(null)}>
        <View style={[styles.modalOverlay, themed.overlay]}>
          <View style={[styles.formModal, { backgroundColor: themed.colors.surface }]}>
            <Text style={[styles.modalTitle, themed.text]}>Bem não localizado</Text>
            <Text style={[styles.formModalText, themed.mutedText]}>{naoLocalizadoBem ? getBemCodigo(naoLocalizadoBem) : '-'}</Text>
            <TextInput
              placeholder="Justificativa"
              placeholderTextColor={themed.colors.textSubtle}
              multiline
              value={justificativa}
              onChangeText={setJustificativa}
              style={[styles.textArea, themed.input]}
            />
            <View style={styles.modalActions}>
              <Pressable onPress={() => setNaoLocalizadoBem(null)} style={[styles.secondaryButton, themed.surface]}>
                <Text style={[styles.secondaryButtonText, themed.primaryText]}>Cancelar</Text>
              </Pressable>
              <Pressable onPress={handleRegistrarNaoLocalizado} style={[styles.dangerButton, { backgroundColor: themed.colors.danger }]}>
                {actionLoading === 'nao-localizado' ? (
                  <ActivityIndicator color={themed.colors.primaryText} />
                ) : (
                  <MaterialIcons name="block" size={19} color={themed.colors.primaryText} />
                )}
                <Text style={[styles.primaryButtonText, themed.onPrimaryText]}>Registrar</Text>
              </Pressable>
            </View>
          </View>
          {renderToast(true)}
        </View>
      </Modal>

      <Modal transparent animationType="fade" visible={divergenciaTarget !== null} onRequestClose={closeDivergencia}>
        <View style={[styles.modalOverlay, themed.overlay]}>
          <View style={[styles.formModal, { backgroundColor: themed.colors.surface }]}>
            <Text style={[styles.modalTitle, themed.text]}>Divergência</Text>
            <Text style={[styles.formModalText, themed.mutedText]}>
              {divergenciaTarget?.bem ? getBemCodigo(divergenciaTarget.bem) : displayValue(divergenciaTarget?.codigo)}
            </Text>
            <TextInput
              placeholder="Campos divergentes"
              placeholderTextColor={themed.colors.textSubtle}
              value={camposDivergentes}
              onChangeText={setCamposDivergentes}
              style={[styles.input, themed.input]}
            />
            <TextInput
              placeholder="Observação"
              placeholderTextColor={themed.colors.textSubtle}
              multiline
              value={observacaoDivergencia}
              onChangeText={setObservacaoDivergencia}
              style={[styles.textArea, themed.input]}
            />
            <View style={styles.modalActions}>
              <Pressable onPress={closeDivergencia} style={[styles.secondaryButton, themed.surface]}>
                <Text style={[styles.secondaryButtonText, themed.primaryText]}>Cancelar</Text>
              </Pressable>
              <Pressable onPress={handleRegistrarDivergencia} style={[styles.primaryButton, { backgroundColor: themed.colors.primary }]}>
                {actionLoading === 'divergencia' ? (
                  <ActivityIndicator color={themed.colors.primaryText} />
                ) : (
                  <MaterialIcons name="save" size={19} color={themed.colors.primaryText} />
                )}
                <Text style={[styles.primaryButtonText, themed.onPrimaryText]}>Salvar</Text>
              </Pressable>
            </View>
          </View>
          {renderToast(true)}
        </View>
      </Modal>

      <FlatList
        data={filteredBens}
        keyExtractor={(item, index) => `${getBemCodigo(item)}-${item.id}-${index}`}
        renderItem={({ item }) => renderBemRow(item)}
        contentContainerStyle={styles.content}
        showsVerticalScrollIndicator={false}
        onEndReached={handleLoadMore}
        onEndReachedThreshold={0.35}
        refreshControl={(
          <RefreshControl
            refreshing={isRefreshing}
            onRefresh={handleRefresh}
            tintColor={themed.colors.primary}
            colors={[themed.colors.primary]}
          />
        )}
        ListHeaderComponent={(
          <View style={styles.headerContent}>
        <View style={styles.header}>
          <AppMenuButton />
          <View style={styles.headerTextGroup}>
            <Text style={[styles.eyebrow, themed.mutedText]}>Conferência de bens</Text>
            <Text style={[styles.title, themed.text]}>Inventário</Text>
          </View>
        </View>

        <View style={[styles.contextPanel, themed.surface]}>
          <View style={[styles.contextIcon, themed.primarySurface]}>
            <MaterialIcons name="apartment" size={23} color={themed.colors.primary} />
          </View>
          <View style={styles.contextText}>
            <Text style={[styles.contextName, themed.text]}>{user?.name ?? user?.login ?? 'Usuário mobile'}</Text>
            <Text style={[styles.contextMeta, themed.mutedText]}>Unidade {user?.unidade_judiciaria ?? '-'} | Setor {user?.setor ?? '-'}</Text>
            <Text style={[styles.contextMeta, themed.mutedText]}>Atividade: {info?.atividade.situacao ?? '-'}</Text>
          </View>
          <View style={[
            styles.lockBadge,
            { backgroundColor: canEdit ? themed.colors.successSoft : themed.colors.surfaceAccent },
          ]}>
            <Text style={[
              styles.lockBadgeText,
              { color: canEdit ? themed.colors.success : themed.colors.textMuted },
            ]}>
              {canEdit ? 'Aberta' : 'Bloqueada'}
            </Text>
          </View>
        </View>

        <View style={styles.metricsPanel}>
          {renderMetric('Total', info?.resumo.total ?? 0)}
          {renderMetric('Localizados', info?.resumo.localizados ?? 0, themed.colors.success)}
          {renderMetric('Pendentes', info?.resumo.pendentes ?? 0, themed.colors.warning)}
          {renderMetric('Não localizados', info?.resumo.nao_localizados ?? 0, themed.colors.danger)}
          {renderMetric('Divergentes', info?.resumo.divergentes ?? 0, themed.colors.info)}
          {renderMetric('Transferência', info?.resumo.em_transferencia ?? 0, themed.colors.info)}
          {renderMetric('Manuais', info?.resumo.cadastrados_manualmente ?? 0, themed.colors.warning)}
        </View>

        <View style={[styles.scanPanel, themed.surface]}>
          <View style={styles.panelHeader}>
            <Text style={[styles.sectionTitle, themed.text]}>Leitura patrimonial</Text>
            <Pressable onPress={handleStartScanner} style={[styles.iconActionButton, themed.primarySurface]}>
              <MaterialIcons name={isScannerActive ? 'close' : 'qr-code-scanner'} size={22} color={themed.colors.primary} />
            </Pressable>
          </View>

          {isScannerActive && cameraPermission?.granted ? (
            <View style={styles.cameraBox}>
              <CameraView
                style={styles.cameraPreview}
                facing="back"
                barcodeScannerSettings={{ barcodeTypes: BARCODE_TYPES }}
                onBarcodeScanned={hasScannedBarcode ? undefined : handleBarcodeScanned}
              />
              <View style={[styles.scanLine, { backgroundColor: themed.colors.success }]} />
            </View>
          ) : null}

          <View style={[styles.manualEntry, themed.input]}>
            <MaterialIcons name="pin" size={20} color={themed.colors.textMuted} />
            <TextInput
              placeholder="Patrimônio"
              placeholderTextColor={themed.colors.textSubtle}
              keyboardType="number-pad"
              inputMode="numeric"
              autoCorrect={false}
              value={manualCode}
              onChangeText={(value) => setManualCode(onlyDigits(value))}
              onSubmitEditing={() => validateCode(manualCode)}
              style={[styles.manualEntryInput, themed.text]}
            />
          </View>

          <Pressable
            disabled={isValidating}
            onPress={() => validateCode(manualCode)}
            style={({ pressed }) => [
              styles.primaryButtonFull,
              { backgroundColor: themed.colors.primary },
              pressed && styles.pressed,
            ]}>
            {isValidating ? (
              <ActivityIndicator color={themed.colors.primaryText} />
            ) : (
              <MaterialIcons name="search" size={20} color={themed.colors.primaryText} />
            )}
            <Text style={[styles.primaryButtonText, themed.onPrimaryText]}>{isValidating ? 'Validando' : 'Validar leitura'}</Text>
          </Pressable>

          {resultadoLeitura ? (
            <View style={[styles.readResultPanel, themed.mutedSurface]}>
              <Text style={[styles.readResultStatus, themed.primaryText]}>{resultadoLeitura.message}</Text>
              {lastReadBem ? (
                <>
                  <Text style={[styles.readResultCode, themed.text]}>{getBemCodigo(lastReadBem)}</Text>
                  <Text style={[styles.readResultDescription, themed.mutedText]}>{getBemDescricao(lastReadBem)}</Text>
                </>
              ) : null}
              {canEdit && (
                resultadoLeitura.pode_localizar
                || ['outro_setor', 'nao_cadastrado'].includes(resultadoLeitura.status)
              ) ? (
                <View style={styles.readResultActions}>
                  {resultadoLeitura.pode_localizar ? (
                    <Pressable onPress={() => handleLocalizar()} style={[styles.confirmButton, { backgroundColor: themed.colors.success }]}>
                      {actionLoading === 'localizar' ? (
                        <ActivityIndicator color={themed.colors.primaryText} />
                      ) : (
                        <MaterialIcons name="check-circle" size={20} color={themed.colors.primaryText} />
                      )}
                      <Text style={[styles.primaryButtonText, themed.onPrimaryText]}>Localizado</Text>
                    </Pressable>
                  ) : null}
                  <Pressable onPress={handleDivergenciaLeitura} style={[styles.divergenceButton, themed.primarySurface]}>
                    <MaterialIcons name="report-problem" size={20} color={themed.colors.primary} />
                    <Text style={[styles.divergenceButtonText, themed.primaryText]}>
                      {resultadoLeitura.pode_localizar ? 'Divergência' : 'Declarar divergência'}
                    </Text>
                  </Pressable>
                </View>
              ) : null}
            </View>
          ) : null}
        </View>

        <View style={styles.filtersPanel}>
          <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.filterList}>
            {FILTERS.map((filter) => {
              const isActive = filterStatus === filter.key;

              return (
                <Pressable
                  key={filter.key}
                  onPress={() => setFilterStatus(filter.key)}
                  style={[
                    styles.filterButton,
                    themed.surface,
                    isActive && themed.primarySurface,
                    isActive && { borderColor: themed.colors.primary },
                  ]}>
                  <Text style={[
                    styles.filterButtonText,
                    themed.mutedText,
                    isActive && themed.primaryText,
                  ]}>{filter.label}</Text>
                </Pressable>
              );
            })}
          </ScrollView>
        </View>

        <View style={[styles.assetsPanel, themed.surface]}>
          <View style={styles.panelHeader}>
            <Text style={[styles.sectionTitle, themed.text]}>Bens do setor</Text>
            <Text style={[styles.panelMeta, themed.mutedText]}>{totalBensListados}</Text>
          </View>

          {isLoadingList ? (
            <View style={[styles.emptyPanel, themed.mutedSurface]}>
              <ActivityIndicator color={themed.colors.primary} />
              <Text style={[styles.emptyTitle, themed.mutedText]}>Carregando bens</Text>
            </View>
          ) : filteredBens.length === 0 ? (
            <View style={[styles.emptyPanel, themed.mutedSurface]}>
              <MaterialIcons name="inventory" size={28} color={themed.colors.textMuted} />
              <Text style={[styles.emptyTitle, themed.mutedText]}>Nenhum bem neste filtro</Text>
            </View>
          ) : null}
        </View>

          </View>
        )}
        ListFooterComponent={(
          <View style={styles.footerContent}>
            {filteredBens.length > 0 ? (
              <View style={styles.listFooter}>
                {isLoadingMore ? (
                  <>
                    <ActivityIndicator color={themed.colors.primary} />
                    <Text style={[styles.listFooterText, themed.mutedText]}>Carregando mais bens</Text>
                  </>
                ) : hasMore ? (
                  <Text style={[styles.listFooterText, themed.mutedText]}>Role para carregar mais</Text>
                ) : (
                  <Text style={[styles.listFooterText, themed.mutedText]}>Todos os bens foram carregados</Text>
                )}
              </View>
            ) : null}

        <Pressable
          disabled={!info?.atividade.pode_finalizar || actionLoading === 'finalizar'}
          onPress={handleFinalizar}
          style={({ pressed }) => [
            styles.finalizeButton,
            { backgroundColor: themed.colors.success },
            (!info?.atividade.pode_finalizar || actionLoading === 'finalizar') && { backgroundColor: themed.colors.textSubtle },
            pressed && styles.pressed,
          ]}>
          {actionLoading === 'finalizar' ? (
            <ActivityIndicator color={themed.colors.primaryText} />
          ) : (
            <MaterialIcons name="task-alt" size={21} color={themed.colors.primaryText} />
          )}
          <Text style={[styles.primaryButtonText, themed.onPrimaryText]}>Finalizar conferência</Text>
        </Pressable>
          </View>
        )}
      />
      <BottomBar />
      {renderToast()}
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: {
    flex: 1,
    backgroundColor: '#F4F7FA',
  },
  content: {
    gap: 14,
    padding: 18,
    paddingBottom: 30,
  },
  headerContent: {
    gap: 14,
  },
  footerContent: {
    gap: 12,
  },
  loadingContainer: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    gap: 12,
  },
  loadingText: {
    color: '#334E68',
    fontSize: 14,
    fontWeight: '800',
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  iconButton: {
    width: 42,
    height: 42,
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: 8,
    borderWidth: 1,
    borderColor: '#B6D4EA',
    backgroundColor: '#EAF4FB',
  },
  headerTextGroup: {
    flex: 1,
    gap: 3,
  },
  eyebrow: {
    color: '#627D98',
    fontSize: 12,
    fontWeight: '800',
    textTransform: 'uppercase',
  },
  title: {
    color: '#102A43',
    fontSize: 23,
    fontWeight: '800',
  },
  toast: {
    position: 'absolute',
    left: 18,
    right: 18,
    zIndex: 30,
    elevation: 12,
    minHeight: 46,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 9,
    borderRadius: 8,
    paddingHorizontal: 12,
    paddingVertical: 10,
    shadowColor: '#102A43',
    shadowOffset: { width: 0, height: 5 },
    shadowOpacity: 0.2,
    shadowRadius: 10,
  },
  screenToast: {
    bottom: 82,
  },
  modalToast: {
    bottom: 20,
  },
  toastSuccess: {
    backgroundColor: '#2F855A',
  },
  toastError: {
    backgroundColor: '#C53030',
  },
  toastInfo: {
    backgroundColor: '#1E4E79',
  },
  toastText: {
    flex: 1,
    color: '#FFFFFF',
    fontSize: 13,
    lineHeight: 18,
    fontWeight: '800',
  },
  contextPanel: {
    minHeight: 84,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: '#D9E2EC',
    backgroundColor: '#FFFFFF',
    padding: 14,
  },
  contextIcon: {
    width: 42,
    height: 42,
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: 8,
    backgroundColor: '#EAF4FB',
  },
  contextText: {
    flex: 1,
    gap: 2,
  },
  contextName: {
    color: '#102A43',
    fontSize: 16,
    fontWeight: '800',
  },
  contextMeta: {
    color: '#52616B',
    fontSize: 12,
    fontWeight: '700',
  },
  lockBadge: {
    borderRadius: 8,
    paddingHorizontal: 8,
    paddingVertical: 6,
  },
  editableBadge: {
    backgroundColor: '#E3F4E9',
  },
  blockedBadge: {
    backgroundColor: '#EEF2F6',
  },
  lockBadgeText: {
    fontSize: 11,
    fontWeight: '800',
  },
  editableBadgeText: {
    color: '#2F855A',
  },
  blockedBadgeText: {
    color: '#627D98',
  },
  metricsPanel: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
  },
  metricItem: {
    minWidth: '31%',
    flexGrow: 1,
    gap: 2,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: '#D9E2EC',
    backgroundColor: '#FFFFFF',
    padding: 12,
  },
  metricValue: {
    fontSize: 22,
    fontWeight: '800',
  },
  metricLabel: {
    color: '#52616B',
    fontSize: 11,
    fontWeight: '800',
    textTransform: 'uppercase',
  },
  scanPanel: {
    gap: 12,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: '#D9E2EC',
    backgroundColor: '#FFFFFF',
    padding: 14,
  },
  panelHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: 10,
  },
  sectionTitle: {
    color: '#102A43',
    fontSize: 17,
    fontWeight: '800',
  },
  panelMeta: {
    color: '#627D98',
    fontSize: 13,
    fontWeight: '800',
  },
  iconActionButton: {
    width: 40,
    height: 40,
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: 8,
    borderWidth: 1,
    borderColor: '#B6D4EA',
    backgroundColor: '#EAF4FB',
  },
  cameraBox: {
    height: 210,
    overflow: 'hidden',
    borderRadius: 8,
    backgroundColor: '#102A43',
  },
  cameraPreview: {
    ...StyleSheet.absoluteFillObject,
  },
  scanLine: {
    position: 'absolute',
    top: '50%',
    left: '12%',
    width: '76%',
    height: 2,
    backgroundColor: '#2F855A',
  },
  manualEntry: {
    minHeight: 48,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: '#D9E2EC',
    backgroundColor: '#FFFFFF',
    paddingHorizontal: 12,
  },
  manualEntryInput: {
    flex: 1,
    color: '#102A43',
    fontSize: 15,
    fontWeight: '800',
  },
  primaryButtonFull: {
    minHeight: 49,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    borderRadius: 8,
    backgroundColor: '#1E4E79',
  },
  readResultPanel: {
    gap: 7,
    borderRadius: 8,
    backgroundColor: '#F8FAFC',
    padding: 12,
  },
  readResultStatus: {
    color: '#1E4E79',
    fontSize: 14,
    fontWeight: '800',
  },
  readResultCode: {
    color: '#102A43',
    fontSize: 20,
    fontWeight: '800',
  },
  readResultDescription: {
    color: '#52616B',
    fontSize: 13,
    lineHeight: 18,
    fontWeight: '700',
  },
  readResultActions: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
    paddingTop: 4,
  },
  confirmButton: {
    flex: 1,
    minWidth: 124,
    minHeight: 44,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    borderRadius: 8,
    backgroundColor: '#2F855A',
  },
  divergenceButton: {
    flex: 1,
    minWidth: 150,
    minHeight: 44,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: '#B6D4EA',
    backgroundColor: '#EAF4FB',
  },
  divergenceButtonText: {
    color: '#1E4E79',
    fontSize: 13,
    fontWeight: '800',
  },
  filtersPanel: {
    minHeight: 44,
  },
  filterList: {
    gap: 8,
    paddingRight: 4,
  },
  filterButton: {
    justifyContent: 'center',
    minHeight: 38,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: '#D9E2EC',
    backgroundColor: '#FFFFFF',
    paddingHorizontal: 12,
  },
  filterButtonActive: {
    borderColor: '#1E4E79',
    backgroundColor: '#EAF4FB',
  },
  filterButtonText: {
    color: '#52616B',
    fontSize: 13,
    fontWeight: '800',
  },
  filterButtonTextActive: {
    color: '#1E4E79',
  },
  assetsPanel: {
    gap: 10,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: '#D9E2EC',
    backgroundColor: '#FFFFFF',
    padding: 14,
  },
  assetRow: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: 10,
    borderRadius: 8,
    backgroundColor: '#F8FAFC',
    padding: 10,
  },
  assetIcon: {
    width: 38,
    height: 38,
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: 8,
    backgroundColor: '#EAF4FB',
  },
  assetInfo: {
    flex: 1,
    gap: 5,
  },
  assetTitleRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: 8,
  },
  assetCode: {
    flex: 1,
    color: '#102A43',
    fontSize: 15,
    fontWeight: '800',
  },
  assetDescription: {
    color: '#334E68',
    fontSize: 13,
    lineHeight: 18,
    fontWeight: '700',
  },
  assetMeta: {
    color: '#627D98',
    fontSize: 12,
    fontWeight: '700',
  },
  statusBadge: {
    maxWidth: 128,
    borderRadius: 8,
    paddingHorizontal: 8,
    paddingVertical: 5,
  },
  statusBadgeText: {
    fontSize: 11,
    fontWeight: '800',
  },
  inlineActions: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
    marginTop: 4,
    paddingTop: 10,
    borderTopWidth: 1,
    borderTopColor: '#E6EDF3',
  },
  inlineActionPrimary: {
    flexBasis: '100%',
    minHeight: 38,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 5,
    borderRadius: 8,
    backgroundColor: '#2F855A',
    paddingHorizontal: 12,
  },
  inlineActionPrimaryText: {
    color: '#FFFFFF',
    fontSize: 12,
    fontWeight: '800',
  },
  inlineActionSecondary: {
    flex: 1,
    minWidth: 128,
    minHeight: 38,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 5,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: '#B6D4EA',
    backgroundColor: '#EAF4FB',
    paddingHorizontal: 10,
  },
  inlineActionDanger: {
    borderColor: '#F1C7C7',
    backgroundColor: '#FFF5F5',
  },
  inlineActionSecondaryText: {
    color: '#1E4E79',
    fontSize: 12,
    fontWeight: '800',
  },
  inlineActionDangerText: {
    color: '#C53030',
    fontSize: 12,
    fontWeight: '800',
  },
  emptyPanel: {
    alignItems: 'center',
    gap: 8,
    borderRadius: 8,
    backgroundColor: '#F8FAFC',
    padding: 18,
  },
  emptyTitle: {
    color: '#52616B',
    fontSize: 14,
    fontWeight: '800',
  },
  listFooter: {
    minHeight: 44,
    alignItems: 'center',
    justifyContent: 'center',
    gap: 6,
  },
  listFooterText: {
    color: '#627D98',
    fontSize: 12,
    fontWeight: '800',
  },
  finalizeButton: {
    minHeight: 50,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    borderRadius: 8,
    backgroundColor: '#2F855A',
  },
  finalizeButtonDisabled: {
    backgroundColor: '#9FB3C8',
  },
  primaryButton: {
    minHeight: 45,
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    borderRadius: 8,
    backgroundColor: '#1E4E79',
  },
  dangerButton: {
    minHeight: 45,
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    borderRadius: 8,
    backgroundColor: '#C53030',
  },
  secondaryButton: {
    minHeight: 45,
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: 8,
    borderWidth: 1,
    borderColor: '#B6D4EA',
    backgroundColor: '#FFFFFF',
  },
  primaryButtonText: {
    color: '#FFFFFF',
    fontSize: 14,
    fontWeight: '800',
  },
  secondaryButtonText: {
    color: '#1E4E79',
    fontSize: 14,
    fontWeight: '800',
  },
  pressed: {
    opacity: 0.72,
  },
  modalOverlay: {
    flex: 1,
    justifyContent: 'center',
    backgroundColor: '#102A4399',
    padding: 18,
  },
  modalPanel: {
    maxHeight: '86%',
    overflow: 'hidden',
    borderRadius: 8,
    backgroundColor: '#FFFFFF',
  },
  formModal: {
    gap: 12,
    borderRadius: 8,
    backgroundColor: '#FFFFFF',
    padding: 16,
  },
  modalHeader: {
    minHeight: 68,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    borderBottomWidth: 1,
    borderBottomColor: '#D9E2EC',
    padding: 14,
  },
  modalHeaderText: {
    flex: 1,
    gap: 3,
  },
  modalEyebrow: {
    color: '#627D98',
    fontSize: 12,
    fontWeight: '800',
    textTransform: 'uppercase',
  },
  modalTitle: {
    color: '#102A43',
    fontSize: 19,
    fontWeight: '800',
  },
  modalContent: {
    gap: 12,
    padding: 14,
  },
  modalDescription: {
    color: '#334E68',
    fontSize: 14,
    lineHeight: 20,
    fontWeight: '700',
  },
  detailGrid: {
    gap: 6,
    borderRadius: 8,
    backgroundColor: '#F8FAFC',
    padding: 12,
  },
  detailLabel: {
    color: '#627D98',
    fontSize: 11,
    fontWeight: '800',
    textTransform: 'uppercase',
  },
  detailValue: {
    color: '#102A43',
    fontSize: 14,
    lineHeight: 19,
    fontWeight: '700',
    marginBottom: 4,
  },
  formModalText: {
    color: '#52616B',
    fontSize: 13,
    fontWeight: '800',
  },
  input: {
    minHeight: 46,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: '#D9E2EC',
    color: '#102A43',
    fontSize: 14,
    fontWeight: '700',
    paddingHorizontal: 12,
  },
  textArea: {
    minHeight: 100,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: '#D9E2EC',
    color: '#102A43',
    fontSize: 14,
    lineHeight: 19,
    fontWeight: '700',
    padding: 12,
    textAlignVertical: 'top',
  },
  modalActions: {
    flexDirection: 'row',
    gap: 9,
  },
});
