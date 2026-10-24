import React, { useEffect, useRef } from "react";
import {
  Animated,
  Dimensions,
  Easing,
  Image,
  StyleSheet,
  Text,
  View,
} from "react-native";
import { StatusBar } from "expo-status-bar";
import { useSafeAreaInsets } from "react-native-safe-area-context";

const { width, height } = Dimensions.get("window");
const PURPLE = "#7367F0";

export default function SplashScreen() {
  const insets = useSafeAreaInsets();

  // Animation values
  const logoScale = useRef(new Animated.Value(0.3)).current;
  const logoOpacity = useRef(new Animated.Value(0)).current;
  const pulseRing = useRef(new Animated.Value(0)).current;
  const textTranslateY = useRef(new Animated.Value(20)).current;
  const textOpacity = useRef(new Animated.Value(0)).current;
  const loaderOpacity = useRef(new Animated.Value(0)).current;

  useEffect(() => {
    // 1. Entrance animation sequence
    Animated.sequence([
      // Logo pop in
      Animated.parallel([
        Animated.spring(logoScale, {
          toValue: 1,
          friction: 6,
          tension: 40,
          useNativeDriver: true,
        }),
        Animated.timing(logoOpacity, {
          toValue: 1,
          duration: 600,
          useNativeDriver: true,
        }),
      ]),
      // Title & Subtitle slide up
      Animated.parallel([
        Animated.timing(textTranslateY, {
          toValue: 0,
          duration: 500,
          easing: Easing.out(Easing.back(1.5)),
          useNativeDriver: true,
        }),
        Animated.timing(textOpacity, {
          toValue: 1,
          duration: 500,
          useNativeDriver: true,
        }),
        Animated.timing(loaderOpacity, {
          toValue: 1,
          duration: 600,
          useNativeDriver: true,
        }),
      ]),
    ]).start();

    // 2. Continuous pulse animation for outer glow ring
    Animated.loop(
      Animated.sequence([
        Animated.timing(pulseRing, {
          toValue: 1,
          duration: 1600,
          easing: Easing.out(Easing.ease),
          useNativeDriver: true,
        }),
        Animated.timing(pulseRing, {
          toValue: 0,
          duration: 0,
          useNativeDriver: true,
        }),
      ])
    ).start();
  }, [
    loaderOpacity,
    logoOpacity,
    logoScale,
    pulseRing,
    textOpacity,
    textTranslateY,
  ]);

  const ringScale = pulseRing.interpolate({
    inputRange: [0, 1],
    outputRange: [1, 1.45],
  });

  const ringOpacity = pulseRing.interpolate({
    inputRange: [0, 0.4, 1],
    outputRange: [0.6, 0.3, 0],
  });

  return (
    <View style={styles.container}>
      <StatusBar style="light" />

      {/* Decorative ambient background lighting */}
      <View style={styles.bgCircleTop} />
      <View style={styles.bgCircleBottom} />

      {/* Center Animated Logo & Branding */}
      <View style={styles.centerContent}>
        {/* Pulsing Outer Glow Ring */}
        <Animated.View
          style={[
            styles.pulseRing,
            {
              transform: [{ scale: ringScale }],
              opacity: ringOpacity,
            },
          ]}
        />

        {/* Real App Icon Logo Container */}
        <Animated.View
          style={[
            styles.logoWrap,
            {
              transform: [{ scale: logoScale }],
              opacity: logoOpacity,
            },
          ]}
        >
          <Image
            source={require("../../assets/icon-screen.png")}
            style={styles.logoImage}
            resizeMode="contain"
          />
        </Animated.View>

        {/* Animated Brand Title & Tagline */}
        <Animated.View
          style={[
            styles.textWrap,
            {
              transform: [{ translateY: textTranslateY }],
              opacity: textOpacity,
            },
          ]}
        >
          <Text style={styles.brandTitle}>AMPL SOLUTION</Text>
          <View style={styles.badgePill}>
            <Text style={styles.badgeText}>FIELDWORK MOBILITY</Text>
          </View>
        </Animated.View>
      </View>

      {/* Minimalist Bottom Loading Indicator with Safe Padding */}
      <Animated.View
        style={[
          styles.footer,
          {
            opacity: loaderOpacity,
            bottom: Math.max(insets.bottom, 20) + 16,
          },
        ]}
      >
        <View style={styles.loadingDotsWrap}>
          <View style={[styles.dot, styles.dot1]} />
          <View style={[styles.dot, styles.dot2]} />
          <View style={[styles.dot, styles.dot3]} />
        </View>
      </Animated.View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: PURPLE,
    alignItems: "center",
    justifyContent: "center",
    overflow: "hidden",
  },
  bgCircleTop: {
    position: "absolute",
    top: -height * 0.15,
    right: -width * 0.2,
    width: width * 0.9,
    height: width * 0.9,
    borderRadius: (width * 0.9) / 2,
    backgroundColor: "rgba(255, 255, 255, 0.08)",
  },
  bgCircleBottom: {
    position: "absolute",
    bottom: -height * 0.15,
    left: -width * 0.2,
    width: width * 0.9,
    height: width * 0.9,
    borderRadius: (width * 0.9) / 2,
    backgroundColor: "rgba(0, 0, 0, 0.08)",
  },
  centerContent: {
    alignItems: "center",
    justifyContent: "center",
  },
  pulseRing: {
    position: "absolute",
    width: 144,
    height: 144,
    borderRadius: 72,
    backgroundColor: "rgba(255, 255, 255, 0.35)",
  },
  logoWrap: {
    width: 120,
    height: 120,
    borderRadius: 32,
    backgroundColor: "rgba(255, 255, 255, 0.2)",
    borderWidth: 2,
    borderColor: "rgba(255, 255, 255, 0.5)",
    overflow: "hidden",
    alignItems: "center",
    justifyContent: "center",
    shadowColor: "#000",
    shadowOffset: { width: 0, height: 10 },
    shadowOpacity: 0.3,
    shadowRadius: 18,
    elevation: 14,
  },
  logoImage: {
    width: 120,
    height: 120,
    borderRadius: 30,
  },
  textWrap: {
    alignItems: "center",
    marginTop: 22,
  },
  brandTitle: {
    color: "#FFFFFF",
    fontSize: 26,
    fontWeight: "900",
    letterSpacing: 1.5,
    textShadowColor: "rgba(0, 0, 0, 0.2)",
    textShadowOffset: { width: 0, height: 2 },
    textShadowRadius: 6,
  },
  badgePill: {
    marginTop: 10,
    backgroundColor: "rgba(255, 255, 255, 0.18)",
    paddingHorizontal: 14,
    paddingVertical: 4.5,
    borderRadius: 20,
    borderWidth: 1,
    borderColor: "rgba(255, 255, 255, 0.25)",
  },
  badgeText: {
    color: "rgba(255, 255, 255, 0.95)",
    fontSize: 11,
    fontWeight: "800",
    letterSpacing: 1.2,
  },
  footer: {
    position: "absolute",
    alignItems: "center",
    justifyContent: "center",
  },
  loadingDotsWrap: {
    flexDirection: "row",
    gap: 7,
  },
  dot: {
    width: 8,
    height: 8,
    borderRadius: 4,
    backgroundColor: "rgba(255, 255, 255, 0.8)",
  },
  dot1: { opacity: 0.4 },
  dot2: { opacity: 0.7 },
  dot3: { opacity: 1 },
});
