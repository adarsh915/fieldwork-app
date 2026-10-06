import React, { useEffect, useRef, useState } from "react";
import {
  findNodeHandle,
  Keyboard,
  KeyboardAvoidingView,
  Platform,
  RefreshControlProps,
  ScrollView,
  ScrollViewProps,
  StyleProp,
  StyleSheet,
  TextInput,
  View,
  ViewStyle,
} from "react-native";
import { Edge, SafeAreaView } from "react-native-safe-area-context";

export interface KeyboardAwareScreenProps {
  children: React.ReactNode;
  contentStyle?: StyleProp<ViewStyle>;
  style?: StyleProp<ViewStyle>;
  keyboardVerticalOffset?: number;
  footer?: React.ReactNode;
  refreshControl?: React.ReactElement<RefreshControlProps>;
  edges?: Edge[];
  scrollViewProps?: Partial<ScrollViewProps>;
  backgroundColor?: string;
  extraScrollHeight?: number;
}

export default function KeyboardAwareScreen({
  children,
  contentStyle,
  style,
  keyboardVerticalOffset = Platform.OS === "ios" ? 64 : 0,
  footer,
  refreshControl,
  edges,
  scrollViewProps,
  backgroundColor = "#F4F4F8",
  extraScrollHeight = 100,
}: KeyboardAwareScreenProps) {
  const scrollViewRef = useRef<ScrollView>(null);
  const [keyboardHeight, setKeyboardHeight] = useState(0);

  const scrollToFocusedInput = () => {
    try {
      const currentlyFocused = (TextInput.State as any)?.currentlyFocusedInput
        ? (TextInput.State as any).currentlyFocusedInput()
        : (TextInput.State as any)?.currentlyFocusedField
        ? (TextInput.State as any).currentlyFocusedField()
        : null;

      if (currentlyFocused && scrollViewRef.current) {
        const scrollResponder = scrollViewRef.current.getScrollResponder();
        const nodeHandle = typeof currentlyFocused === "number" ? currentlyFocused : findNodeHandle(currentlyFocused);
        if (nodeHandle && scrollResponder?.scrollResponderScrollNativeHandleToKeyboard) {
          scrollResponder.scrollResponderScrollNativeHandleToKeyboard(
            nodeHandle,
            extraScrollHeight,
            true
          );
        }
      }
    } catch {
      // safe fallback
    }
  };

  useEffect(() => {
    const showEvent = Platform.OS === "ios" ? "keyboardWillShow" : "keyboardDidShow";
    const hideEvent = Platform.OS === "ios" ? "keyboardWillHide" : "keyboardDidHide";

    const showSub = Keyboard.addListener(showEvent, (e) => {
      setKeyboardHeight(e.endCoordinates?.height || 280);
      setTimeout(scrollToFocusedInput, 50);
    });
    const hideSub = Keyboard.addListener(hideEvent, () => {
      setKeyboardHeight(0);
    });

    return () => {
      showSub.remove();
      hideSub.remove();
    };
  }, [extraScrollHeight]);

  const containerStyle: StyleProp<ViewStyle> = [
    styles.container,
    { backgroundColor },
    style,
  ];

  const content = (
    <KeyboardAvoidingView
      style={styles.flex}
      behavior={Platform.OS === "ios" ? "padding" : "height"}
      keyboardVerticalOffset={keyboardVerticalOffset}
    >
      <ScrollView
        ref={scrollViewRef}
        style={styles.flex}
        contentContainerStyle={[
          styles.contentContainer,
          contentStyle,
          {
            paddingBottom: keyboardHeight > 0 ? keyboardHeight + extraScrollHeight : 40,
          },
        ]}
        keyboardShouldPersistTaps="handled"
        keyboardDismissMode="on-drag"
        showsVerticalScrollIndicator={false}
        refreshControl={refreshControl}
        {...scrollViewProps}
      >
        {children}
      </ScrollView>

      {footer ? (
        <View style={styles.footerWrap}>
          {footer}
        </View>
      ) : null}
    </KeyboardAvoidingView>
  );

  if (edges && edges.length > 0) {
    return (
      <SafeAreaView style={containerStyle} edges={edges}>
        {content}
      </SafeAreaView>
    );
  }

  return <View style={containerStyle}>{content}</View>;
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
  flex: {
    flex: 1,
  },
  contentContainer: {
    flexGrow: 1,
    padding: 16,
    paddingBottom: 40,
  },
  footerWrap: {
    paddingHorizontal: 16,
    paddingVertical: 12,
    backgroundColor: "#FFFFFF",
    borderTopWidth: 1,
    borderTopColor: "#E5E7EB",
  },
});
