import { MaterialCommunityIcons } from "@expo/vector-icons";
import React, {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useRef,
  useState,
} from "react";
import {
  ActivityIndicator,
  Alert as RNAlert,
  Animated,
  Modal,
  StyleSheet,
  Text,
  TouchableOpacity,
  TouchableWithoutFeedback,
  View,
} from "react-native";

export type AlertType = "success" | "error" | "warning" | "info" | "confirm";

export interface ModalButton {
  text: string;
  style?: "default" | "cancel" | "destructive";
  onPress?: () => void | Promise<void>;
}

export interface AlertOptions {
  type?: AlertType;
  title?: string;
  message?: string;
  confirmText?: string;
  cancelText?: string;
  isDestructive?: boolean;
  onConfirm?: () => void | Promise<void>;
  onCancel?: () => void;
  buttons?: ModalButton[];
  autoCloseMs?: number;
}

interface AlertModalContextType {
  showAlert: (options: AlertOptions) => void;
  showSuccess: (title: string, message?: string, onConfirm?: () => void) => void;
  showError: (title: string, message?: string) => void;
  showWarning: (title: string, message?: string) => void;
  showInfo: (title: string, message?: string) => void;
  showConfirm: (options: {
    title: string;
    message: string;
    confirmText?: string;
    cancelText?: string;
    isDestructive?: boolean;
    onConfirm: () => void | Promise<void>;
    onCancel?: () => void;
  }) => void;
  hideAlert: () => void;
}

const AlertModalContext = createContext<AlertModalContextType | undefined>(
  undefined
);

// Global static reference so non-hooks, callbacks, or monkey-patched Alert.alert can trigger alerts
let globalShowAlert: ((options: AlertOptions) => void) | null = null;
let globalHideAlert: (() => void) | null = null;
let pendingAlert: AlertOptions | null = null;

export const showAlertModal = (options: AlertOptions) => {
  if (globalShowAlert) {
    globalShowAlert(options);
  } else {
    pendingAlert = options;
  }
};

export const hideAlertModal = () => {
  if (globalHideAlert) {
    globalHideAlert();
  }
};

/**
 * Global monkey-patch for React Native's default Alert.alert
 * Ensures EVERY native Alert.alert(...) call in any screen or component
 * is routed directly to this beautiful custom AlertModal!
 */
let isPatched = false;
export function patchReactNativeAlert() {
  if (isPatched) return;
  isPatched = true;

  RNAlert.alert = (
    title?: string,
    message?: string,
    buttons?: any[],
    alertOptions?: any
  ) => {
    const cleanTitle = title || "";
    const cleanMessage = message || "";
    const lower = (cleanTitle + " " + cleanMessage).toLowerCase();

    // 1. Detect type intelligently based on keywords
    let type: AlertType = "info";
    if (
      lower.includes("success") ||
      lower.includes("🎉") ||
      lower.includes("great") ||
      lower.includes("saved") ||
      lower.includes("submitted") ||
      lower.includes("ended") ||
      lower.includes("added") ||
      lower.includes("recorded") ||
      lower.includes("completed")
    ) {
      type = "success";
    } else if (
      lower.includes("error") ||
      lower.includes("failed") ||
      lower.includes("fail") ||
      lower.includes("invalid") ||
      lower.includes("denied") ||
      lower.includes("wrong")
    ) {
      type = "error";
    } else if (
      lower.includes("warning") ||
      lower.includes("required") ||
      lower.includes("attention") ||
      lower.includes("missing") ||
      lower.includes("permission needed") ||
      lower.includes("permission required") ||
      lower.includes("⚠️")
    ) {
      type = "warning";
    } else if (
      buttons &&
      buttons.length >= 2 &&
      (lower.includes("logout") ||
        lower.includes("confirm") ||
        lower.includes("sure") ||
        lower.includes("delete") ||
        lower.includes("remove") ||
        lower.includes("cancel"))
    ) {
      type = "confirm";
    }

    // 2. Parse button configurations
    if (buttons && buttons.length > 0) {
      if (buttons.length === 1) {
        showAlertModal({
          type,
          title: cleanTitle,
          message: cleanMessage,
          confirmText: buttons[0].text || "Got It",
          onConfirm: buttons[0].onPress,
        });
      } else if (buttons.length === 2) {
        const cancelBtn =
          buttons.find((b) => b.style === "cancel") || buttons[0];
        const confirmBtn =
          buttons.find((b) => b !== cancelBtn) || buttons[1];

        showAlertModal({
          type: type === "info" ? "confirm" : type,
          title: cleanTitle,
          message: cleanMessage,
          cancelText: cancelBtn.text || "Cancel",
          confirmText: confirmBtn.text || "Confirm",
          isDestructive: confirmBtn.style === "destructive",
          onCancel: cancelBtn.onPress,
          onConfirm: confirmBtn.onPress,
        });
      } else {
        // 3 or more buttons -> Action list (e.g. Upload Proof -> Camera, Gallery, Cancel)
        showAlertModal({
          type: "info",
          title: cleanTitle,
          message: cleanMessage,
          buttons: buttons.map((b) => ({
            text: b.text || "Option",
            style: b.style || "default",
            onPress: b.onPress,
          })),
        });
      }
    } else {
      // No buttons provided -> default single OK button
      showAlertModal({
        type,
        title: cleanTitle,
        message: cleanMessage,
        confirmText: "Got It",
      });
    }
  };
}

// Automatically apply the patch upon module initialization
patchReactNativeAlert();

const PURPLE = "#7367F0";

const TYPE_CONFIG = {
  success: {
    icon: "check-circle-outline" as const,
    color: "#10B981",
    bgColor: "#ECFDF5",
    borderColor: "#A7F3D0",
    defaultTitle: "Success!",
    defaultButtonText: "Great, Got It",
    btnBg: "#10B981",
  },
  error: {
    icon: "alert-circle-outline" as const,
    color: "#EF4444",
    bgColor: "#FEF2F2",
    borderColor: "#FECACA",
    defaultTitle: "Oops, Something Went Wrong",
    defaultButtonText: "Dismiss",
    btnBg: "#EF4444",
  },
  warning: {
    icon: "alert-outline" as const,
    color: "#F59E0B",
    bgColor: "#FFFBEB",
    borderColor: "#FDE68A",
    defaultTitle: "Attention",
    defaultButtonText: "Understand",
    btnBg: "#F59E0B",
  },
  info: {
    icon: "information-outline" as const,
    color: "#3B82F6",
    bgColor: "#EFF6FF",
    borderColor: "#BFDBFE",
    defaultTitle: "Notice",
    defaultButtonText: "OK",
    btnBg: "#3B82F6",
  },
  confirm: {
    icon: "help-circle-outline" as const,
    color: PURPLE,
    bgColor: "#EDE9FE",
    borderColor: "#DDD6FE",
    defaultTitle: "Please Confirm",
    defaultButtonText: "Yes, Confirm",
    btnBg: PURPLE,
  },
};

export const AlertModalProvider: React.FC<{ children: React.ReactNode }> = ({
  children,
}) => {
  const [visible, setVisible] = useState(false);
  const [loading, setLoading] = useState(false);
  const [options, setOptions] = useState<AlertOptions | null>(null);

  const scaleAnim = useRef(new Animated.Value(0.85)).current;
  const opacityAnim = useRef(new Animated.Value(0)).current;
  const autoCloseTimer = useRef<NodeJS.Timeout | null>(null);

  const hideAlert = useCallback(() => {
    if (autoCloseTimer.current) {
      clearTimeout(autoCloseTimer.current);
      autoCloseTimer.current = null;
    }

    Animated.parallel([
      Animated.timing(scaleAnim, {
        toValue: 0.85,
        duration: 150,
        useNativeDriver: true,
      }),
      Animated.timing(opacityAnim, {
        toValue: 0,
        duration: 150,
        useNativeDriver: true,
      }),
    ]).start(() => {
      setVisible(false);
      setLoading(false);
      setOptions(null);
    });
  }, [opacityAnim, scaleAnim]);

  const showAlert = useCallback(
    (opts: AlertOptions) => {
      if (autoCloseTimer.current) {
        clearTimeout(autoCloseTimer.current);
        autoCloseTimer.current = null;
      }

      setOptions(opts);
      setVisible(true);
      setLoading(false);

      Animated.parallel([
        Animated.spring(scaleAnim, {
          toValue: 1,
          friction: 6,
          tension: 60,
          useNativeDriver: true,
        }),
        Animated.timing(opacityAnim, {
          toValue: 1,
          duration: 200,
          useNativeDriver: true,
        }),
      ]).start();

      if (opts.autoCloseMs && opts.autoCloseMs > 0) {
        autoCloseTimer.current = setTimeout(() => {
          hideAlert();
        }, opts.autoCloseMs);
      }
    },
    [hideAlert, opacityAnim, scaleAnim]
  );

  const showSuccess = useCallback(
    (title: string, message?: string, onConfirm?: () => void) => {
      showAlert({
        type: "success",
        title: message ? title : "Success!",
        message: message || title,
        onConfirm,
      });
    },
    [showAlert]
  );

  const showError = useCallback(
    (title: string, message?: string) => {
      showAlert({
        type: "error",
        title: message ? title : "Error",
        message: message || title,
      });
    },
    [showAlert]
  );

  const showWarning = useCallback(
    (title: string, message?: string) => {
      showAlert({
        type: "warning",
        title: message ? title : "Attention",
        message: message || title,
      });
    },
    [showAlert]
  );

  const showInfo = useCallback(
    (title: string, message?: string) => {
      showAlert({
        type: "info",
        title: message ? title : "Notice",
        message: message || title,
      });
    },
    [showAlert]
  );

  const showConfirm = useCallback(
    (opts: {
      title: string;
      message: string;
      confirmText?: string;
      cancelText?: string;
      isDestructive?: boolean;
      onConfirm: () => void | Promise<void>;
      onCancel?: () => void;
    }) => {
      showAlert({
        type: "confirm",
        ...opts,
      });
    },
    [showAlert]
  );

  useEffect(() => {
    globalShowAlert = showAlert;
    globalHideAlert = hideAlert;
    patchReactNativeAlert();

    // If any alert was triggered prior to mount, flush it now
    if (pendingAlert) {
      showAlert(pendingAlert);
      pendingAlert = null;
    }

    return () => {
      globalShowAlert = null;
      globalHideAlert = null;
    };
  }, [showAlert, hideAlert]);

  const currentType = options?.type || "info";
  const config = TYPE_CONFIG[currentType];
  const isConfirmDialog = currentType === "confirm" || !!options?.onCancel;
  const hasMultipleButtons = !!(options?.buttons && options.buttons.length > 0);

  const handleConfirmPress = async () => {
    if (options?.onConfirm) {
      try {
        const result = options.onConfirm();
        if (result instanceof Promise) {
          setLoading(true);
          await result;
        }
      } catch (e) {
        console.error("Error executing modal confirm action:", e);
      }
    }
    hideAlert();
  };

  const handleCancelPress = () => {
    if (options?.onCancel) {
      options.onCancel();
    }
    hideAlert();
  };

  return (
    <AlertModalContext.Provider
      value={{
        showAlert,
        showSuccess,
        showError,
        showWarning,
        showInfo,
        showConfirm,
        hideAlert,
      }}
    >
      {children}

      <Modal
        visible={visible}
        transparent
        animationType="none"
        statusBarTranslucent
        onRequestClose={() => {
          if (!loading) hideAlert();
        }}
      >
        <TouchableWithoutFeedback onPress={() => !loading && hideAlert()}>
          <View style={styles.backdrop}>
            <TouchableWithoutFeedback>
              <Animated.View
                style={[
                  styles.modalBox,
                  {
                    transform: [{ scale: scaleAnim }],
                    opacity: opacityAnim,
                  },
                ]}
              >
                {/* ── Top Icon Badge ── */}
                <View
                  style={[
                    styles.iconCircle,
                    {
                      backgroundColor: config.bgColor,
                      borderColor: config.borderColor,
                    },
                  ]}
                >
                  <MaterialCommunityIcons
                    name={config.icon}
                    size={38}
                    color={
                      options?.isDestructive && currentType === "confirm"
                        ? "#DC2626"
                        : config.color
                    }
                  />
                </View>

                {/* ── Title ── */}
                <Text style={styles.titleText}>
                  {options?.title || config.defaultTitle}
                </Text>

                {/* ── Message Body ── */}
                {!!options?.message && (
                  <Text style={styles.messageText}>{options.message}</Text>
                )}

                {/* ── Action Buttons ── */}
                {hasMultipleButtons ? (
                  <View style={styles.buttonColumn}>
                    {options!.buttons!.map((btn, idx) => {
                      const isCancel = btn.style === "cancel";
                      const isDestructive = btn.style === "destructive";
                      return (
                        <TouchableOpacity
                          key={idx}
                          style={[
                            styles.actionOptionBtn,
                            isCancel && styles.actionOptionBtnCancel,
                            isDestructive && styles.actionOptionBtnDestructive,
                          ]}
                          activeOpacity={0.85}
                          onPress={async () => {
                            if (btn.onPress) {
                              try {
                                const res = btn.onPress();
                                if (res instanceof Promise) {
                                  setLoading(true);
                                  await res;
                                }
                              } catch (e) {
                                console.error("Button press error:", e);
                              }
                            }
                            hideAlert();
                          }}
                          disabled={loading}
                        >
                          <Text
                            style={[
                              styles.actionOptionBtnText,
                              isCancel && styles.actionOptionBtnTextCancel,
                            ]}
                          >
                            {btn.text}
                          </Text>
                        </TouchableOpacity>
                      );
                    })}
                  </View>
                ) : isConfirmDialog ? (
                  <View style={styles.buttonRow}>
                    <TouchableOpacity
                      style={styles.cancelButton}
                      activeOpacity={0.8}
                      onPress={handleCancelPress}
                      disabled={loading}
                    >
                      <Text style={styles.cancelButtonText}>
                        {options?.cancelText || "Cancel"}
                      </Text>
                    </TouchableOpacity>

                    <TouchableOpacity
                      style={[
                        styles.confirmButton,
                        {
                          backgroundColor: options?.isDestructive
                            ? "#DC2626"
                            : config.btnBg,
                        },
                      ]}
                      activeOpacity={0.85}
                      onPress={handleConfirmPress}
                      disabled={loading}
                    >
                      {loading ? (
                        <ActivityIndicator color="#FFFFFF" size="small" />
                      ) : (
                        <Text style={styles.confirmButtonText}>
                          {options?.confirmText || "Confirm"}
                        </Text>
                      )}
                    </TouchableOpacity>
                  </View>
                ) : (
                  <TouchableOpacity
                    style={[
                      styles.singleButton,
                      { backgroundColor: config.btnBg },
                    ]}
                    activeOpacity={0.85}
                    onPress={handleConfirmPress}
                  >
                    <Text style={styles.singleButtonText}>
                      {options?.confirmText || config.defaultButtonText}
                    </Text>
                  </TouchableOpacity>
                )}
              </Animated.View>
            </TouchableWithoutFeedback>
          </View>
        </TouchableWithoutFeedback>
      </Modal>
    </AlertModalContext.Provider>
  );
};

export const useAlert = () => {
  const context = useContext(AlertModalContext);
  if (!context) {
    throw new Error("useAlert must be used within an AlertModalProvider");
  }
  return context;
};

// Convenient alias for useAlert
export const useAlertModal = useAlert;

const styles = StyleSheet.create({
  backdrop: {
    flex: 1,
    backgroundColor: "rgba(15, 23, 42, 0.65)",
    justifyContent: "center",
    alignItems: "center",
    padding: 24,
  },
  modalBox: {
    width: "100%",
    maxWidth: 330,
    backgroundColor: "#FFFFFF",
    borderRadius: 24,
    paddingHorizontal: 22,
    paddingTop: 26,
    paddingBottom: 22,
    alignItems: "center",
    shadowColor: "#000",
    shadowOffset: { width: 0, height: 10 },
    shadowOpacity: 0.15,
    shadowRadius: 20,
    elevation: 10,
  },
  iconCircle: {
    width: 68,
    height: 68,
    borderRadius: 34,
    alignItems: "center",
    justifyContent: "center",
    marginBottom: 16,
    borderWidth: 2,
  },
  titleText: {
    fontSize: 18,
    fontWeight: "800",
    color: "#0F172A",
    textAlign: "center",
    marginBottom: 8,
    letterSpacing: -0.2,
  },
  messageText: {
    fontSize: 14,
    color: "#64748B",
    textAlign: "center",
    lineHeight: 20,
    marginBottom: 22,
    paddingHorizontal: 6,
  },
  buttonRow: {
    flexDirection: "row",
    gap: 10,
    width: "100%",
  },
  cancelButton: {
    flex: 1,
    height: 46,
    borderRadius: 14,
    backgroundColor: "#F1F5F9",
    alignItems: "center",
    justifyContent: "center",
  },
  cancelButtonText: {
    fontSize: 14,
    fontWeight: "700",
    color: "#64748B",
  },
  confirmButton: {
    flex: 1,
    height: 46,
    borderRadius: 14,
    alignItems: "center",
    justifyContent: "center",
    shadowColor: "#000",
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 2,
  },
  confirmButtonText: {
    fontSize: 14,
    fontWeight: "800",
    color: "#FFFFFF",
  },
  singleButton: {
    width: "100%",
    height: 48,
    borderRadius: 14,
    alignItems: "center",
    justifyContent: "center",
    shadowColor: "#000",
    shadowOpacity: 0.12,
    shadowRadius: 5,
    elevation: 3,
  },
  singleButtonText: {
    fontSize: 15,
    fontWeight: "800",
    color: "#FFFFFF",
    letterSpacing: 0.2,
  },
  buttonColumn: {
    width: "100%",
    gap: 10,
  },
  actionOptionBtn: {
    width: "100%",
    height: 46,
    borderRadius: 14,
    backgroundColor: PURPLE,
    alignItems: "center",
    justifyContent: "center",
    shadowColor: "#000",
    shadowOpacity: 0.08,
    shadowRadius: 3,
    elevation: 2,
  },
  actionOptionBtnCancel: {
    backgroundColor: "#F1F5F9",
    shadowOpacity: 0,
    elevation: 0,
  },
  actionOptionBtnDestructive: {
    backgroundColor: "#EF4444",
  },
  actionOptionBtnText: {
    fontSize: 14,
    fontWeight: "800",
    color: "#FFFFFF",
  },
  actionOptionBtnTextCancel: {
    color: "#64748B",
    fontWeight: "700",
  },
});
